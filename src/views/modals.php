<?php
/**
 * Modal Component
 * Generic modal template for forms, dialogs, and content display
 * Usage:
 * - renderModal('modalId', 'Modal Title', $content, 'large');
 */

function renderModal($id, $title, $content = '', $size = 'medium', $submitText = 'Submit', $cancelText = 'Cancel') {
    $sizeClass = [
        'small' => 'max-w-sm',
        'medium' => 'max-w-md',
        'large' => 'max-w-lg',
        'xlarge' => 'max-w-2xl'
    ][$size] ?? 'max-w-md';
    
    echo <<<HTML
    <!-- Modal: $id -->
    <div id="$id" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto $sizeClass">
            <div class="bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">$title</h3>
                    <button onclick="closeModal('$id')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
                        ×
                    </button>
                </div>
                
                <!-- Content -->
                <div id="${id}Content" class="p-6">
                    $content
                </div>
                
                <!-- Footer -->
                <div class="flex gap-3 justify-end p-6 border-t border-gray-200">
                    <button onclick="closeModal('$id')" class="px-4 py-2 text-gray-700 border border-gray-300 rounded hover:bg-gray-50 transition">
                        $cancelText
                    </button>
                    <button id="${id}SubmitBtn" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 transition">
                        $submitText
                    </button>
                </div>
            </div>
        </div>
    </div>
    HTML;
}

function renderLoadingModal($id = 'loadingModal', $message = 'Loading...') {
    echo <<<HTML
    <!-- Loading Modal -->
    <div id="$id" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 h-full w-full z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl p-8 text-center">
            <div class="spinner mx-auto mb-4"></div>
            <p class="text-gray-700">$message</p>
        </div>
    </div>
    
    <script>
        function showLoadingModal(message = '$message') {
            document.getElementById('$id').classList.remove('hidden');
            const contentEl = document.getElementById('${id}Content');
            if (contentEl) contentEl.textContent = message;
        }
        
        function hideLoadingModal() {
            document.getElementById('$id').classList.add('hidden');
        }
    </script>
    HTML;
}

function renderConfirmModal($id = 'confirmModal', $title = 'Confirm Action') {
    echo <<<HTML
    <!-- Confirm Modal -->
    <div id="$id" class="hidden fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
        <div class="relative top-20 mx-auto max-w-md">
            <div class="bg-white rounded-lg shadow-xl">
                <!-- Header -->
                <div class="flex items-center justify-between p-6 border-b border-gray-200">
                    <h3 class="text-xl font-semibold text-gray-900">$title</h3>
                    <button onclick="closeModal('$id')" class="text-gray-400 hover:text-gray-600 text-2xl leading-none">
                        ×
                    </button>
                </div>
                
                <!-- Content -->
                <div class="p-6">
                    <p id="${id}Message" class="text-gray-700"></p>
                </div>
                
                <!-- Footer -->
                <div class="flex gap-3 justify-end p-6 border-t border-gray-200">
                    <button onclick="closeModal('$id')" class="px-4 py-2 text-gray-700 border border-gray-300 rounded hover:bg-gray-50 transition">
                        Cancel
                    </button>
                    <button id="${id}ConfirmBtn" class="px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700 transition">
                        Confirm
                    </button>
                </div>
            </div>
        </div>
    </div>
    HTML;
}
