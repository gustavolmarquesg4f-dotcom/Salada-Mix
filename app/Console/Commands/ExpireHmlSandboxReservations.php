<?php

namespace App\Console\Commands;

use App\Domain\Orders\HmlSandboxOrderService;
use App\Support\HmlDemo;
use Illuminate\Console\Command;

final class ExpireHmlSandboxReservations extends Command
{
    protected $signature = 'marketplace:expire-hml-sandbox {--limit=50}';

    protected $description = 'Libera reservas de estoque vencidas da jornada SANDBOX exclusiva da HML.';

    public function handle(HmlSandboxOrderService $orders): int
    {
        if (! HmlDemo::enabled()) {
            $this->line('HML sandbox não está habilitada neste ambiente.');

            return self::SUCCESS;
        }

        $limit = filter_var($this->option('limit'), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 200],
        ]);
        if ($limit === false) {
            $this->error('O limite deve estar entre 1 e 200.');

            return self::INVALID;
        }

        $count = $orders->expireDue((int) $limit);
        $this->info("Reservas SANDBOX expiradas: {$count}");

        return self::SUCCESS;
    }
}
