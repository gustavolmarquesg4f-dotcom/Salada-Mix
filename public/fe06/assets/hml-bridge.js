"use strict";
/* Only shipped in Hostinger HML. The approved FE-06 remains the visual shell,
 * but all commerce navigation goes to Laravel's server-side, DB-backed pages. */
(() => {
  if (location.hostname !== "ivory-rook-276202.hostingersite.com") return;
  const routes = {
    "/": "/loja",
    "/buscar": "/buscar",
    "/departamentos": "/buscar",
    "/ofertas": "/buscar",
    "/conta": "/minha-conta",
    "/entrar": "/entrar",
    "/cadastro": "/cadastro",
    "/recuperar": "/esqueci-a-senha",
    "/enderecos": "/minha-conta/enderecos",
    "/sacola": "/sacola",
    "/favoritos": "/favoritos",
    "/preparar-compra": "/demo",
    "/resumo": "/demo",
    "/vender/cadastro": "/vender/cadastro",
    "/vendedor": "/demo/painel/vendedor",
    "/admin": "/demo/painel/admin"
  };
  const resolve = hash => {
    const raw = hash.replace(/^#/, "");
    const [pathname, query = ""] = raw.split("?");
    if (pathname.startsWith("/categorias/")) return pathname;
    if (pathname.startsWith("/ofertas/demo-")) return "/demo/ofertas/" + encodeURIComponent(pathname.slice("/ofertas/demo-".length));
    const path = routes[pathname];
    if (!path) return null;
    return path + (query ? "?" + query : "");
  };
  document.addEventListener("click", event => {
    const target = event.target instanceof Element ? event.target : event.target.parentElement;
    const anchor = target?.closest('a[href^="#/"]');
    if (anchor) {
      const path = resolve(anchor.getAttribute("href"));
      if (path) {
        event.preventDefault();
        event.stopImmediatePropagation();
        location.assign(path);
      }
      return;
    }
    const button = target?.closest("[data-demo-action]");
    if (button) {
      const id = button.getAttribute("data-id") || "";
      const path = id.startsWith("demo-")
        ? "/demo/ofertas/" + encodeURIComponent(id.slice(5))
        : "/buscar";
      event.preventDefault();
      event.stopImmediatePropagation();
      location.assign(path);
    }
  }, true);
  document.addEventListener("submit", event => {
    const form = event.target;
    if (!(form instanceof HTMLFormElement)) return;
    if (form.id !== "global-search" && form.id !== "browse-form") return;
    event.preventDefault();
    event.stopImmediatePropagation();
    const params = new URLSearchParams(new FormData(form));
    // Preview seller values are display names; Laravel's filter expects a ULID.
    // Let the real /buscar screen offer the correctly populated DB seller selector.
    if (params.has("seller") && !/^[0-9A-HJKMNP-TV-Z]{26}$/i.test(params.get("seller"))) params.delete("seller");
    const category = params.get("category") || form.dataset.category || "";
    params.delete("category");
    location.assign((category ? "/categorias/" + encodeURIComponent(category) : "/buscar") + "?" + params.toString());
  }, true);
  window.addEventListener("DOMContentLoaded", () => {
    const marker = document.querySelector(".sm-status");
    if (marker) {
      marker.innerHTML = '<strong>HOMOLOGAÇÃO · NAVEGAÇÃO INTEGRADA</strong> — FE-06 visual com jornadas reais do Laravel. <a href="/demo">Abrir guia de demonstração funcional →</a>';
    }
  });
})();