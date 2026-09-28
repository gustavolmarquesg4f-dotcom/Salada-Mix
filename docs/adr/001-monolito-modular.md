# ADR-001 — Monólito modular

Status: aceito em 2026-09-28.
Contexto: marketplace aberto com equipe de manutenção PHP, hospedagem Hostinger VPS KVM 2.
Decisão: Laravel 13 + Blade/Livewire 4 + MySQL 8.4; módulos por domínio, um repositório e deploy de aplicação.
API pública e separação física de serviços ficam para quando houver requisitos e métricas.
Consequências: baixo esforço operacional inicial; VPS e MySQL locais representam ponto único de falha. Backup externo e plano de restauração são obrigatórios antes de produção.
