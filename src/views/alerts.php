<?php
/**
 * Alert Helper Functions
 * Usage:
 * - showAlert('Success message', 'success');
 * - showAlert('Error occurred', 'error');
 */

function showAlert($message, $type = 'info') {
    $bgColor = [
        'success' => 'bg-green-50 border-green-200',
        'error' => 'bg-red-50 border-red-200',
        'warning' => 'bg-yellow-50 border-yellow-200',
        'info' => 'bg-blue-50 border-blue-200'
    ][$type] ?? 'bg-blue-50 border-blue-200';
    
    $textColor = [
        'success' => 'text-green-800',
        'error' => 'text-red-800',
        'warning' => 'text-yellow-800',
        'info' => 'text-blue-800'
    ][$type] ?? 'text-blue-800';
    
    $icon = [
        'success' => '✓',
        'error' => '✕',
        'warning' => '⚠',
        'info' => 'ℹ'
    ][$type] ?? 'ℹ';
    
    echo <<<HTML
    <div class="$bgColor border-l-4 p-4 mb-4 rounded">
        <div class="flex items-center">
            <span class="font-bold mr-2">$icon</span>
            <p class="$textColor">
                {$message}
            </p>
            <button onclick="this.parentElement.parentElement.remove()" class="ml-auto text-gray-400 hover:text-gray-600">
                ✕
            </button>
        </div>
    </div>
    HTML;
}

function showErrorAlert($errors) {
    if (is_array($errors)) {
        $errorList = '<ul class="list-disc list-inside mt-2">';
        foreach ($errors as $error) {
            $errorList .= '<li>' . htmlspecialchars($error) . '</li>';
        }
        $errorList .= '</ul>';
    } else {
        $errorList = htmlspecialchars($errors);
    }
    
    echo <<<HTML
    <div class="bg-red-50 border-l-4 border-red-200 p-4 mb-4 rounded">
        <div class="flex items-start">
            <span class="font-bold mr-2">✕</span>
            <div class="flex-1">
                <p class="text-red-800 font-semibold">Error</p>
                <div class="text-red-700 text-sm mt-1">
                    $errorList
                </div>
            </div>
            <button onclick="this.parentElement.parentElement.remove()" class="text-gray-400 hover:text-gray-600">
                ✕
            </button>
        </div>
    </div>
    HTML;
}

function showValidationErrors($errors) {
    return showErrorAlert($errors);
}
