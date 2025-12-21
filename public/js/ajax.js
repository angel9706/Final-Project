// jQuery AJAX Helper untuk CRUD Operations
// Replaces fetch API dengan $.ajax() untuk semua operations

class AjaxClient {
    constructor() {
        this.apiBaseUrl = '/siapkak/api';
        this.timeout = 30000; // 30 seconds
    }

    /**
     * Get authorization header
     */
    getAuthHeaders() {
        const token = localStorage.getItem('auth_token');
        return token ? { 'Authorization': `Bearer ${token}` } : {};
    }

    /**
     * Make AJAX request
     */
    request(endpoint, options = {}) {
        const url = `${this.apiBaseUrl}${endpoint}`;
        const self = this;

        return new Promise((resolve, reject) => {
            $.ajax({
                url: url,
                type: options.method || 'GET',
                data: options.data ? JSON.stringify(options.data) : null,
                dataType: 'json',
                contentType: 'application/json',
                headers: self.getAuthHeaders(),
                timeout: self.timeout,
                success: function(response) {
                    resolve(response);
                },
                error: function(xhr, status, error) {
                    const errorMessage = xhr.responseJSON?.message || error || 'AJAX Error';
                    reject(new Error(errorMessage));
                }
            });
        });
    }

    /**
     * GET request
     */
    get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    }

    /**
     * POST request
     */
    post(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'POST',
            data: data
        });
    }

    /**
     * PUT request
     */
    put(endpoint, data = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            data: data
        });
    }

    /**
     * DELETE request
     */
    delete(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'DELETE' });
    }
}

// Global AJAX client instance
const ajaxClient = new AjaxClient();

/**
 * jQuery AJAX Form Handler untuk CRUD
 * Gunakan pada form dengan data-ajax-endpoint dan data-ajax-method
 */
class FormAjaxHandler {
    constructor() {
        this.bindForms();
    }

    bindForms() {
        $(document).on('submit', 'form[data-ajax-endpoint]', (e) => {
            e.preventDefault();
            this.handleFormSubmit(e.target);
        });
    }

    /**
     * Handle form submission via AJAX
     */
    async handleFormSubmit(form) {
        const $form = $(form);
        const endpoint = $form.data('ajax-endpoint');
        const method = $form.data('ajax-method') || 'POST';
        const successCallback = $form.data('ajax-success');
        const errorCallback = $form.data('ajax-error');

        if (!endpoint) {
            console.error('Form missing data-ajax-endpoint attribute');
            return;
        }

        // Get form data
        const formData = new FormData(form);
        const data = Object.fromEntries(formData);

        // Show loading state
        const $submitBtn = $form.find('button[type="submit"]');
        const originalText = $submitBtn.text();
        $submitBtn.prop('disabled', true).text('Processing...');

        try {
            // Make AJAX request
            let response;
            if (method.toUpperCase() === 'GET') {
                response = await ajaxClient.get(endpoint, data);
            } else if (method.toUpperCase() === 'POST') {
                response = await ajaxClient.post(endpoint, data);
            } else if (method.toUpperCase() === 'PUT') {
                response = await ajaxClient.put(endpoint, data);
            } else if (method.toUpperCase() === 'DELETE') {
                response = await ajaxClient.delete(endpoint, data);
            }

            // Show success message
            if (response.success) {
                this.showAlert('success', response.message || 'Operation successful');
                
                // Reset form
                form.reset();
                
                // Execute custom success callback
                if (successCallback && typeof window[successCallback] === 'function') {
                    window[successCallback](response.data);
                }

                // Trigger custom event
                $form.trigger('ajax:success', [response.data]);
            } else {
                this.showAlert('error', response.message || 'Operation failed');
            }
        } catch (error) {
            this.showAlert('error', error.message || 'An error occurred');
            
            // Execute custom error callback
            if (errorCallback && typeof window[errorCallback] === 'function') {
                window[errorCallback](error);
            }

            // Trigger custom event
            $form.trigger('ajax:error', [error]);
        } finally {
            // Restore button state
            $submitBtn.prop('disabled', false).text(originalText);
        }
    }

    /**
     * Show alert notification
     */
    showAlert(type, message) {
        const alertHTML = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        
        // Prepend to body atau parent container
        const $container = $('.alert-container') || $('body');
        $container.prepend(alertHTML);

        // Auto-hide setelah 5 seconds
        setTimeout(() => {
            $container.find('.alert').fadeOut(() => {
                $(this).remove();
            });
        }, 5000);
    }
}

// jQuery Modal Handler untuk Create/Edit
class ModalAjaxHandler {
    constructor(modalSelector, formSelector) {
        this.$modal = $(modalSelector);
        this.$form = $(formSelector);
        this.bindEvents();
    }

    bindEvents() {
        // Open modal for create
        $(document).on('click', '[data-action="create"]', (e) => {
            e.preventDefault();
            this.$form[0].reset();
            this.$form.data('ajax-method', 'POST');
            this.$form.data('ajax-endpoint', this.$form.data('ajax-endpoint-create'));
            this.$modal.modal('show');
        });

        // Open modal for edit
        $(document).on('click', '[data-action="edit"]', (e) => {
            e.preventDefault();
            const id = $(e.target).closest('[data-id]').data('id');
            this.loadDataForEdit(id);
        });

        // Delete
        $(document).on('click', '[data-action="delete"]', (e) => {
            e.preventDefault();
            const id = $(e.target).closest('[data-id]').data('id');
            this.confirmDelete(id);
        });
    }

    async loadDataForEdit(id) {
        try {
            const endpoint = this.$form.data('ajax-endpoint-get') + `?id=${id}`;
            const response = await ajaxClient.get(endpoint);

            if (response.success && response.data) {
                this.populateForm(response.data);
                this.$form.data('ajax-method', 'PUT');
                this.$form.data('ajax-endpoint', this.$form.data('ajax-endpoint-update'));
                this.$form.data('edit-id', id);
                this.$modal.modal('show');
            }
        } catch (error) {
            alert('Failed to load data: ' + error.message);
        }
    }

    populateForm(data) {
        for (const [key, value] of Object.entries(data)) {
            const $field = this.$form.find(`[name="${key}"]`);
            if ($field.length) {
                $field.val(value);
            }
        }
    }

    async confirmDelete(id) {
        if (!confirm('Are you sure you want to delete this item?')) {
            return;
        }

        try {
            const endpoint = this.$form.data('ajax-endpoint-delete') + `?id=${id}`;
            const response = await ajaxClient.delete(endpoint);

            if (response.success) {
                alert('Item deleted successfully');
                location.reload();
            } else {
                alert('Failed to delete: ' + response.message);
            }
        } catch (error) {
            alert('Delete error: ' + error.message);
        }
    }
}

// jQuery Table/List Handler untuk menampilkan data
class TableAjaxHandler {
    constructor(tableSelector, dataEndpoint) {
        this.$table = $(tableSelector);
        this.dataEndpoint = dataEndpoint;
        this.loadData();
    }

    async loadData(page = 1) {
        try {
            const response = await ajaxClient.get(this.dataEndpoint, { page: page, limit: 10 });

            if (response.success) {
                this.renderTable(response.data);
            }
        } catch (error) {
            console.error('Failed to load table data:', error);
        }
    }

    renderTable(data) {
        const $tbody = this.$table.find('tbody');
        $tbody.empty();

        if (!data || data.length === 0) {
            $tbody.html('<tr><td colspan="100%" class="text-center">No data available</td></tr>');
            return;
        }

        data.forEach((row) => {
            const $row = $(`<tr data-id="${row.id}"></tr>`);
            
            // Add cells
            for (const [key, value] of Object.entries(row)) {
                if (key !== 'id') {
                    $row.append(`<td>${value}</td>`);
                }
            }

            // Add actions
            $row.append(`
                <td>
                    <button class="btn btn-sm btn-primary" data-action="edit">Edit</button>
                    <button class="btn btn-sm btn-danger" data-action="delete">Delete</button>
                </td>
            `);

            $tbody.append($row);
        });
    }
}

// Initialize form handler ketika document ready
$(document).ready(function() {
    new FormAjaxHandler();
});
