<?php
/**
 * Form Components
 * Reusable form elements for consistent UI
 */

function renderFormInput($name, $label = '', $type = 'text', $value = '', $required = false, $placeholder = '') {
    $requiredAttr = $required ? 'required' : '';
    $requiredLabel = $required ? '<span class="text-red-600">*</span>' : '';
    
    echo <<<HTML
    <div class="mb-4">
        <label for="$name" class="block text-sm font-medium text-gray-700 mb-1">
            $label
            $requiredLabel
        </label>
        <input 
            type="$type" 
            id="$name" 
            name="$name" 
            value="$value"
            placeholder="$placeholder"
            $requiredAttr
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
        >
    </div>
    HTML;
}

function renderFormTextarea($name, $label = '', $value = '', $required = false, $rows = 4) {
    $requiredAttr = $required ? 'required' : '';
    $requiredLabel = $required ? '<span class="text-red-600">*</span>' : '';
    
    echo <<<HTML
    <div class="mb-4">
        <label for="$name" class="block text-sm font-medium text-gray-700 mb-1">
            $label
            $requiredLabel
        </label>
        <textarea 
            id="$name" 
            name="$name" 
            rows="$rows"
            $requiredAttr
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
        >$value</textarea>
    </div>
    HTML;
}

function renderFormSelect($name, $label = '', $options = [], $selected = '', $required = false) {
    $requiredAttr = $required ? 'required' : '';
    $requiredLabel = $required ? '<span class="text-red-600">*</span>' : '';
    
    $optionsHtml = '';
    foreach ($options as $value => $text) {
        $selectedAttr = ($value === $selected) ? 'selected' : '';
        $optionsHtml .= "<option value=\"$value\" $selectedAttr>" . htmlspecialchars($text) . "</option>";
    }
    
    echo <<<HTML
    <div class="mb-4">
        <label for="$name" class="block text-sm font-medium text-gray-700 mb-1">
            $label
            $requiredLabel
        </label>
        <select 
            id="$name" 
            name="$name" 
            $requiredAttr
            class="w-full px-3 py-2 border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-green-500 focus:border-green-500"
        >
            <option value="">-- Select $label --</option>
            $optionsHtml
        </select>
    </div>
    HTML;
}

function renderFormCheckbox($name, $label = '', $value = '1', $checked = false) {
    $checkedAttr = $checked ? 'checked' : '';
    
    echo <<<HTML
    <div class="mb-4 flex items-center">
        <input 
            type="checkbox" 
            id="$name" 
            name="$name" 
            value="$value"
            $checkedAttr
            class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300 rounded"
        >
        <label for="$name" class="ml-2 block text-sm text-gray-700">
            $label
        </label>
    </div>
    HTML;
}

function renderFormRadio($name, $label = '', $options = [], $selected = '') {
    $optionsHtml = '';
    foreach ($options as $value => $text) {
        $id = "{$name}_" . str_replace(' ', '_', $value);
        $checkedAttr = ($value === $selected) ? 'checked' : '';
        $optionsHtml .= <<<HTML
        <div class="flex items-center mb-2">
            <input 
                type="radio" 
                id="$id" 
                name="$name" 
                value="$value"
                $checkedAttr
                class="h-4 w-4 text-green-600 focus:ring-green-500 border-gray-300"
            >
            <label for="$id" class="ml-2 block text-sm text-gray-700">
                $text
            </label>
        </div>
        HTML;
    }
    
    echo <<<HTML
    <div class="mb-4">
        <label class="block text-sm font-medium text-gray-700 mb-2">
            $label
        </label>
        $optionsHtml
    </div>
    HTML;
}

function renderFormGroup($content) {
    echo '<div class="bg-gray-50 p-4 rounded-md border border-gray-200">' . $content . '</div>';
}

function renderSubmitButton($text = 'Submit', $loading = false) {
    $disabledAttr = $loading ? 'disabled' : '';
    $content = $loading ? '<span class="spinner inline-block mr-2"></span>Loading...' : $text;
    
    echo <<<HTML
    <button 
        type="submit" 
        $disabledAttr
        class="w-full bg-green-600 text-white py-2 px-4 rounded-md hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 transition disabled:bg-gray-400 disabled:cursor-not-allowed"
    >
        $content
    </button>
    HTML;
}
