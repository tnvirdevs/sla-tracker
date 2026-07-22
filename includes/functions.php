<?php
/**
 * functions.php
 * Shared helper functions used throughout the application.
 */

if (!function_exists('e')) {
    /**
     * Escape a value for safe HTML output.
     */
    function e(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
    }
}

/**
 * Defensively truncate a string to a max length (in characters) so that
 * oversized input can never trigger a database-level "data too long"
 * error under strict SQL mode. Client-side maxlength attributes are the
 * primary UX guard; this is the server-side backstop.
 */
function str_limit(?string $value, int $max): string
{
    $value = (string) ($value ?? '');
    if (function_exists('mb_substr')) {
        return mb_substr($value, 0, $max);
    }
    return substr($value, 0, $max);
}

/**
 * Neutralize CSV/formula injection. If a field's first character would be
 * interpreted as a formula trigger by Excel/LibreOffice/Google Sheets
 * (=, +, -, @, tab, or carriage return), prefix it with a single quote so
 * spreadsheet software treats it as plain text instead of executing it.
 * See OWASP "CSV Injection".
 */
function csv_safe(?string $value): string
{
    $value = (string) ($value ?? '');
    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $value;
    }
    return $value;
}

function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function get_flash(): ?array
{
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function redirect(string $path): void
{
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function old(string $key, $default = '')
{
    return $_SESSION['old'][$key] ?? $default;
}

function clear_old(): void
{
    unset($_SESSION['old']);
}

function input(string $key, $default = null)
{
    $value = $_POST[$key] ?? $_GET[$key] ?? $default;
    return is_string($value) ? trim($value) : $value;
}

/**
 * Format seconds into "03h 22m" style duration. Negative numbers are
 * automatically expressed as a positive duration by the caller.
 */
function format_duration(int $seconds): string
{
    $seconds = abs($seconds);
    $days = intdiv($seconds, 86400);
    $seconds %= 86400;
    $hours = intdiv($seconds, 3600);
    $seconds %= 3600;
    $minutes = intdiv($seconds, 60);

    if ($days > 0) {
        return sprintf('%dd %02dh %02dm', $days, $hours, $minutes);
    }
    return sprintf('%02dh %02dm', $hours, $minutes);
}

function format_datetime(?string $datetime, string $format = 'M d, Y h:i A'): string
{
    if (empty($datetime)) {
        return '&mdash;';
    }
    return date($format, strtotime($datetime));
}

/**
 * Returns a Bootstrap badge class for a given record status.
 */
function status_badge_class(string $status): string
{
    return match ($status) {
        'Open'        => 'bg-primary',
        'In Progress' => 'bg-info text-dark',
        'Completed'   => 'bg-secondary',
        'Cancelled'   => 'bg-dark',
        default       => 'bg-light text-dark',
    };
}

function priority_badge_class(string $priority): string
{
    return match ($priority) {
        'Critical' => 'bg-danger',
        'High'     => 'bg-warning text-dark',
        'Medium'   => 'bg-info text-dark',
        'Low'      => 'bg-success',
        default    => 'bg-light text-dark',
    };
}

/**
 * Returns the Bootstrap color word (used for text-*, bg-*, border-*) for
 * an SLA state color key (green/yellow/red/gray).
 */
function sla_color_class(string $color): string
{
    return match ($color) {
        'green'  => 'success',
        'yellow' => 'warning',
        'red'    => 'danger',
        'gray'   => 'secondary',
        default  => 'secondary',
    };
}

/**
 * Simple pagination link builder that preserves the current query string.
 */
function pagination_links(int $currentPage, int $totalPages, array $queryParams = []): string
{
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav aria-label="Page navigation"><ul class="pagination justify-content-center mb-0">';

    $buildUrl = function (int $page) use ($queryParams) {
        $queryParams['page'] = $page;
        return '?' . http_build_query($queryParams);
    };

    $prevDisabled = $currentPage <= 1 ? ' disabled' : '';
    $html .= '<li class="page-item' . $prevDisabled . '"><a class="page-link" href="' . e($buildUrl(max(1, $currentPage - 1))) . '">&laquo;</a></li>';

    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . e($buildUrl(1)) . '">1</a></li>';
        if ($start > 2) {
            $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $currentPage ? ' active' : '';
        $html .= '<li class="page-item' . $active . '"><a class="page-link" href="' . e($buildUrl($i)) . '">' . $i . '</a></li>';
    }

    if ($end < $totalPages) {
        if ($end < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><span class="page-link">&hellip;</span></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . e($buildUrl($totalPages)) . '">' . $totalPages . '</a></li>';
    }

    $nextDisabled = $currentPage >= $totalPages ? ' disabled' : '';
    $html .= '<li class="page-item' . $nextDisabled . '"><a class="page-link" href="' . e($buildUrl(min($totalPages, $currentPage + 1))) . '">&raquo;</a></li>';

    $html .= '</ul></nav>';
    return $html;
}
