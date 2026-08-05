<?php
function set_flash(string $type, string $message)
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function redirect_to(string $url)
{
    header('Location: ' . $url);
    exit;
}

function e(?string $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function current_tahun_ajaran(): ?string
{
    return $_SESSION['tahun_ajaran'] ?? null;
}

function poin_badge(int $poin): string
{
    if ($poin > 75) {
        return '<span class="badge badge-danger">' . $poin . '</span>';
    }
    if ($poin >= 51) {
        return '<span class="badge badge-warning">' . $poin . '</span>';
    }
    if ($poin >= 26) {
        return '<span class="badge badge-warning">' . $poin . '</span>';
    }
    return '<span class="badge badge-good">' . $poin . '</span>';
}
