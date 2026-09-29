/* FE-05. Cart and wishlist changes are authenticated same-origin BFF requests.
 * Price, availability and subtotal ALWAYS come from Laravel; no local cart state. */
const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
const allowed = new Set(['cart-add', 'cart-remove', 'wishlist-add', 'wishlist-remove']);
const text = (el, message, isError=false) => {
    el.textContent = message;
    el.classList.toggle('is-error', isError);
    el.hidden = false;
};
const showCommerceFeedback = (message, isError=false) => {
    let el = document.getElementById('sm-commerce-feedback');
    if (!el) {
        el = document.createElement('div');
        el.id = 'sm-commerce-feedback';
        el.className = 'sm-commerce-feedback';
        el.setAttribute('role', 'status');
        el.setAttribute('aria-live', 'polite');
        document.body.append(el);
    }
    text(el, message, isError);
};
async function requestCommerce(url, method, body) {
    if (!csrf) throw new Error('Sua sessão expirou. Atualize a página.');
    const response = await fetch(url, {
        method, credentials: 'same-origin', redirect: 'follow',
        headers: { 'Accept': 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
        ...(body ? {body: JSON.stringify(body)} : {})
    });
    if (response.status === 419) throw new Error('Sua sessão expirou. Atualize a página e tente novamente.');
    if (response.status === 401 || response.status === 403) throw new Error('Entre na conta e confirme o e-mail para continuar.');
    let result;
    try { result = await response.json(); } catch (_) { throw new Error('Não foi possível ler a resposta do servidor.'); }
    if (!response.ok) {
        const validation = result?.errors ? Object.values(result.errors).flat().filter(x => typeof x === 'string') : [];
        throw new Error(validation.join(' ') || result?.message || 'Não foi possível atualizar. Tente novamente.');
    }
    return result;
}
function handleCommerceError(error) {
    showCommerceFeedback(error?.message || 'Não foi possível atualizar sua sacola.', true);
}
if (csrf) {
    document.addEventListener('click', async event => {
        const button = event.target.closest('[data-sm-commerce]');
        if (!button || !allowed.has(button.dataset.smCommerce) || button.disabled) return;
        const kind = button.dataset.smCommerce;
        const method = kind.endsWith('remove') ? 'DELETE' : 'PUT';
        button.disabled = true;
        showCommerceFeedback('Atualizando suas escolhas…');
        try {
            await requestCommerce(button.dataset.url, method, kind === 'cart-add' ? {quantity: 1} : undefined);
            showCommerceFeedback('Atualizado! Carregando dados confirmados pelo servidor…');
            window.location.reload();
        } catch (error) {
            handleCommerceError(error);
            button.disabled = false;
        }
    });
    document.addEventListener('change', async event => {
        const select = event.target.closest('[data-sm-cart-qty]');
        if (!select || select.disabled) return;
        const quantity = Number(select.value);
        if (!Number.isInteger(quantity) || quantity < 1 || quantity > 20) {
            select.value = select.dataset.original;
            return showCommerceFeedback('Informe uma quantidade entre 1 e 20.', true);
        }
        select.disabled = true;
        showCommerceFeedback('Revalidando o estoque e o subtotal…');
        try {
            await requestCommerce(select.dataset.url, 'PUT', {quantity});
            window.location.reload();
        } catch (error) {
            select.value = select.dataset.original;
            select.disabled = false;
            handleCommerceError(error);
        }
    });
}
