<?php

namespace App\Http\Controllers\Storefront;

use App\Domain\Catalog\Queries\PublicCatalog;
use App\Http\Controllers\Controller;
use App\Models\ProductMedia;
use App\Models\Seller;
use App\Models\SellerOffer;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

final class ProductMediaController extends Controller
{
    public function show(Request $request, ProductMedia $media, PublicCatalog $catalog): BinaryFileResponse
    {
        $visible = $catalog->visibleOffers()->where('product_id', $media->product_id)->exists();
        $member = $request->user() && DB::table('seller_memberships')
            ->where('seller_id', $media->seller_id)->where('user_id', $request->user()->id)
            ->where('status', 'active')->exists();
        $admin = $request->user()?->platform_role === 'admin';
        abort_unless($visible || $member || $admin, 404);
        $disk = Storage::disk('local');
        abort_unless($disk->exists($media->path), 404);
        return response()->file($disk->path($media->path), [
            'Content-Type' => $media->mime,
            'X-Content-Type-Options' => 'nosniff',
            'Cache-Control' => $visible ? 'public, max-age=3600' : 'private, no-store',
        ]);
    }

    public function sellerStore(Request $request, Seller $seller, SellerOffer $offer): RedirectResponse
    {
        abort_unless($offer->seller_id === $seller->id, 404);
        abort_unless($seller->memberships()->where('user_id', $request->user()->id)
            ->where('status', 'active')->whereIn('role', ['owner', 'manager'])->exists(), 403);
        $this->save($request, $offer);
        return back()->with('status', 'Foto cadastrada para revisão.');
    }

    public function adminStore(Request $request, SellerOffer $offer): RedirectResponse
    {
        abort_unless($request->user()?->platform_role === 'admin', 403);
        $this->save($request, $offer);
        return back()->with('status', 'Foto cadastrada no produto.');
    }

    public function adminCover(Request $request, SellerOffer $offer, ProductMedia $media): RedirectResponse
    {
        abort_unless($request->user()?->platform_role === 'admin', 403);
        abort_unless($media->product_id === $offer->product_id && $media->seller_id === $offer->seller_id, 404);

        DB::transaction(function () use ($request, $offer, $media): void {
            $rows = ProductMedia::query()->where('product_id', $offer->product_id)
                ->orderBy('position')->orderBy('id')->lockForUpdate()->get();
            $ordered = $rows->sortBy(fn (ProductMedia $row): int => $row->id === $media->id ? -1 : $row->position)->values();
            foreach ($ordered as $position => $row) {
                if ((int) $row->position !== $position) {
                    $row->update(['position' => $position]);
                }
            }
            DB::table('audit_logs')->insert([
                'id' => (string) Str::ulid(), 'actor_user_id' => $request->user()->id,
                'seller_id' => $offer->seller_id, 'action' => 'catalog.media.cover_changed',
                'metadata' => json_encode(['product_id' => $offer->product_id, 'media_id' => $media->id], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        }, 3);

        return back()->with('status', 'Imagem definida como capa.');
    }

    public function adminDestroy(Request $request, SellerOffer $offer, ProductMedia $media): RedirectResponse
    {
        abort_unless($request->user()?->platform_role === 'admin', 403);
        abort_unless($media->product_id === $offer->product_id && $media->seller_id === $offer->seller_id, 404);

        DB::transaction(function () use ($request, $offer, $media): void {
            $locked = ProductMedia::query()->whereKey($media->id)->lockForUpdate()->firstOrFail();
            $path = $locked->path;
            $locked->delete();

            $remaining = ProductMedia::query()->where('product_id', $offer->product_id)
                ->orderBy('position')->orderBy('id')->get();
            foreach ($remaining as $position => $row) {
                if ((int) $row->position !== $position) {
                    $row->update(['position' => $position]);
                }
            }

            DB::table('audit_logs')->insert([
                'id' => (string) Str::ulid(), 'actor_user_id' => $request->user()->id,
                'seller_id' => $offer->seller_id, 'action' => 'catalog.media.deleted',
                'metadata' => json_encode(['product_id' => $offer->product_id, 'media_id' => $media->id], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);

            Storage::disk('local')->delete($path);
        }, 3);

        return back()->with('status', 'Imagem removida e galeria reorganizada.');
    }

    private function save(Request $request, SellerOffer $offer): void
    {
        $data = $request->validate([
            'image' => ['required', 'image', 'mimetypes:image/jpeg,image/png,image/webp',
                'max:4096', 'dimensions:min_width=300,min_height=300,max_width=4000,max_height=4000'],
            'alt' => ['required', 'string', 'min:3', 'max:160'],
        ]);
        $product = $offer->product;
        $count = ProductMedia::query()->where('product_id', $product->id)->count();
        if ($count >= 5) {
            throw ValidationException::withMessages(['image' => 'Limite de 5 imagens por produto.']);
        }
        $file = $data['image'];
        $mime = $file->getMimeType();
        $extension = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/webp' => 'webp'][$mime] ?? null;
        if (! $extension) {
            throw ValidationException::withMessages(['image' => 'Formato de imagem não permitido.']);
        }
        $path = 'product-media/'.$product->id.'/'.Str::lower((string) Str::ulid()).'.'.$extension;
        $disk = Storage::disk('local');
        if (! $disk->putFileAs(dirname($path), $file, basename($path))) {
            throw ValidationException::withMessages(['image' => 'Falha ao salvar a imagem.']);
        }
        try {
            ProductMedia::query()->create([
                'product_id' => $product->id, 'seller_id' => $offer->seller_id,
                'uploaded_by' => $request->user()->id, 'path' => $path,
                'mime' => $mime, 'alt' => trim($data['alt']), 'position' => $count,
            ]);
            DB::table('audit_logs')->insert([
                'id' => (string) Str::ulid(), 'actor_user_id' => $request->user()->id,
                'seller_id' => $offer->seller_id, 'action' => 'catalog.media.added',
                'metadata' => json_encode(['product_id' => $product->id], JSON_THROW_ON_ERROR),
                'created_at' => now(),
            ]);
        } catch (\Throwable $exception) {
            $disk->delete($path);
            throw $exception;
        }
    }
}
