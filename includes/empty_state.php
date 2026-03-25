<?php
function renderEmptyChartState($icon, $title, $message)
{
    return '
    <div class="empty-state">
        <div class="empty-state-icon"><i class="' . htmlspecialchars($icon) . '"></i></div>
        <div class="empty-state-title">' . htmlspecialchars($title) . '</div>
        <div class="empty-state-message">' . htmlspecialchars($message) . '</div>
    </div>
    ';
}
