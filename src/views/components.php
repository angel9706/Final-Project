<?php
/**
 * Table Components
 * Reusable table rendering for consistent UI
 */

function renderTable($data, $columns, $actions = true, $responsive = true) {
    if (empty($data)) {
        echo '<p class="text-center text-gray-500 py-4">No data found</p>';
        return;
    }
    
    $responsive_class = $responsive ? 'overflow-x-auto' : '';
    
    echo <<<HTML
    <div class="$responsive_class">
        <table class="min-w-full divide-y divide-gray-200 border border-gray-200">
            <thead class="bg-gray-100">
                <tr>
    HTML;
    
    // Render headers
    foreach ($columns as $key => $label) {
        echo "<th class=\"px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider\">" . htmlspecialchars($label) . "</th>";
    }
    
    if ($actions) {
        echo '<th class="px-6 py-3 text-left text-xs font-medium text-gray-700 uppercase tracking-wider">Actions</th>';
    }
    
    echo <<<HTML
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
    HTML;
    
    // Render rows
    foreach ($data as $row) {
        echo '<tr class="hover:bg-gray-50">';
        
        foreach ($columns as $key => $label) {
            $value = isset($row[$key]) ? htmlspecialchars($row[$key]) : '-';
            echo "<td class=\"px-6 py-4 whitespace-nowrap text-sm text-gray-900\">$value</td>";
        }
        
        if ($actions) {
            $id = $row['id'] ?? '';
            echo <<<HTML
            <td class="px-6 py-4 whitespace-nowrap text-sm space-x-2">
                <button onclick="editItem('$id')" class="text-blue-600 hover:text-blue-900">Edit</button>
                <button onclick="deleteItem('$id')" class="text-red-600 hover:text-red-900">Delete</button>
            </td>
            HTML;
        }
        
        echo '</tr>';
    }
    
    echo <<<HTML
            </tbody>
        </table>
    </div>
    HTML;
}

function renderCard($title, $content, $footer = '') {
    echo <<<HTML
    <div class="bg-white rounded-lg shadow-md p-6 border border-gray-200">
        <h3 class="text-lg font-semibold text-gray-900 mb-4">$title</h3>
        <div class="text-gray-700">
            $content
        </div>
    HTML;
    
    if (!empty($footer)) {
        echo "<div class=\"mt-4 pt-4 border-t border-gray-200\">$footer</div>";
    }
    
    echo '</div>';
}

function renderStatCard($title, $value, $icon = '📊', $change = null, $color = 'green') {
    $colorClass = [
        'green' => 'bg-green-50 text-green-700 border-green-200',
        'blue' => 'bg-blue-50 text-blue-700 border-blue-200',
        'red' => 'bg-red-50 text-red-700 border-red-200',
        'yellow' => 'bg-yellow-50 text-yellow-700 border-yellow-200'
    ][$color] ?? 'bg-green-50 text-green-700 border-green-200';
    
    $changeHtml = '';
    if ($change !== null) {
        $changeValue = abs($change);
        $changeColor = $change >= 0 ? 'text-green-600' : 'text-red-600';
        $changeSymbol = $change >= 0 ? '↑' : '↓';
        $changeHtml = "<p class=\"text-sm $changeColor\">$changeSymbol $changeValue% from last month</p>";
    }
    
    echo <<<HTML
    <div class="$colorClass border rounded-lg p-6 shadow-sm">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm font-medium opacity-75">$title</p>
                <p class="text-3xl font-bold mt-2">$value</p>
                $changeHtml
            </div>
            <div class="text-4xl">$icon</div>
        </div>
    </div>
    HTML;
}

function renderBadge($text, $type = 'info') {
    $classes = [
        'info' => 'bg-blue-100 text-blue-800',
        'success' => 'bg-green-100 text-green-800',
        'warning' => 'bg-yellow-100 text-yellow-800',
        'danger' => 'bg-red-100 text-red-800',
        'gray' => 'bg-gray-100 text-gray-800'
    ][$type] ?? 'bg-blue-100 text-blue-800';
    
    echo "<span class=\"$classes px-3 py-1 rounded-full text-xs font-medium\">" . htmlspecialchars($text) . "</span>";
}

function renderPagination($currentPage, $totalPages, $baseUrl = '#') {
    if ($totalPages <= 1) return;
    
    echo <<<HTML
    <div class="flex justify-center items-center space-x-2 mt-6">
    HTML;
    
    // Previous button
    if ($currentPage > 1) {
        $prevPage = $currentPage - 1;
        echo "<a href=\"$baseUrl?page=$prevPage\" class=\"px-3 py-2 border border-gray-300 rounded hover:bg-gray-50\">Previous</a>";
    }
    
    // Page numbers
    $start = max(1, $currentPage - 2);
    $end = min($totalPages, $currentPage + 2);
    
    if ($start > 1) {
        echo "<a href=\"$baseUrl?page=1\" class=\"px-3 py-2 border border-gray-300 rounded hover:bg-gray-50\">1</a>";
        if ($start > 2) echo "<span class=\"px-2\">...</span>";
    }
    
    for ($i = $start; $i <= $end; $i++) {
        $activeClass = $i === $currentPage ? 'bg-green-600 text-white border-green-600' : 'border-gray-300';
        echo "<a href=\"$baseUrl?page=$i\" class=\"px-3 py-2 border rounded $activeClass\">$i</a>";
    }
    
    if ($end < $totalPages) {
        if ($end < $totalPages - 1) echo "<span class=\"px-2\">...</span>";
        echo "<a href=\"$baseUrl?page=$totalPages\" class=\"px-3 py-2 border border-gray-300 rounded hover:bg-gray-50\">$totalPages</a>";
    }
    
    // Next button
    if ($currentPage < $totalPages) {
        $nextPage = $currentPage + 1;
        echo "<a href=\"$baseUrl?page=$nextPage\" class=\"px-3 py-2 border border-gray-300 rounded hover:bg-gray-50\">Next</a>";
    }
    
    echo '</div>';
}
