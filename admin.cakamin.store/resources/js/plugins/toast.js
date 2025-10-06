/**
 * Toast Notification System
 * A sleek, customizable toast notification system for the reseller site
 */

(function() {
    // Define the ToastNotification class
    class ToastNotification {
        constructor(options = {}) {
            // Default settings
            this.settings = {
                position: options.position || 'top-right',
                duration: options.duration || 1500,
                maxToasts: options.maxToasts || 5,
                pauseOnHover: options.pauseOnHover !== undefined ? options.pauseOnHover : true,
                container: null // Will be set when DOM is ready
            };

            // Wait for DOM to be fully ready before accessing document.body
            if (document.readyState === "loading") {
                document.addEventListener("DOMContentLoaded", () => {
                    this.settings.container = document.body;
                    this.initContainer();
                });
            } else {
                this.settings.container = document.body;
                this.initContainer();
            }

            // Toast counter
            this.toastCount = 0;

            // Active toasts
            this.activeToasts = [];
        }

        /**
         * Initialize the toast container
         */
        initContainer() {
            // Safety check for document.body
            if (!document.body) {
                setTimeout(() => this.initContainer(), 50);
                return;
            }

            // Check if container already exists
            const existingContainer = document.querySelector('.toast-container');
            if (existingContainer) {
                this.container = existingContainer;
                return;
            }

            // Create container
            this.container = document.createElement('div');
            this.container.className = 'toast-container';

            // Position the container
            switch (this.settings.position) {
                case 'top-left':
                    this.container.style.top = '20px';
                    this.container.style.left = '20px';
                    this.container.style.right = 'auto';
                    this.container.style.bottom = 'auto';
                    break;
                case 'top-center':
                    this.container.style.top = '20px';
                    this.container.style.left = '50%';
                    this.container.style.transform = 'translateX(-50%)';
                    this.container.style.right = 'auto';
                    this.container.style.bottom = 'auto';
                    break;
                case 'bottom-right':
                    this.container.style.bottom = '20px';
                    this.container.style.right = '20px';
                    this.container.style.top = 'auto';
                    this.container.style.left = 'auto';
                    break;
                case 'bottom-left':
                    this.container.style.bottom = '20px';
                    this.container.style.left = '20px';
                    this.container.style.top = 'auto';
                    this.container.style.right = 'auto';
                    break;
                case 'bottom-center':
                    this.container.style.bottom = '20px';
                    this.container.style.left = '50%';
                    this.container.style.transform = 'translateX(-50%)';
                    this.container.style.top = 'auto';
                    this.container.style.right = 'auto';
                    break;
                default: // top-right
                    this.container.style.top = '20px';
                    this.container.style.right = '20px';
                    this.container.style.left = 'auto';
                    this.container.style.bottom = 'auto';
            }

            // Ensure we have a valid container to append to
            if (this.settings.container) {
                this.settings.container.appendChild(this.container);
            } else if (document.body) {
                document.body.appendChild(this.container);
            } else {
                console.error('Toast container could not be initialized - document.body not available');
            }
        }

        /**
         * Show a toast notification
         * @param {Object} options - Toast options
         */
        show(options) {
            // Ensure DOM is ready and container exists
            if (!this.container) {
                // If DOM is not ready yet, queue this toast for when it is
                if (!document.body) {
                    const showWhenReady = () => {
                        this.show(options);
                        document.removeEventListener('DOMContentLoaded', showWhenReady);
                    };
                    document.addEventListener('DOMContentLoaded', showWhenReady);
                    return { id: null, remove: () => {}, update: () => {} }; // Return dummy toast
                }
                this.initContainer();
            }

            // Check if we already have max number of toasts
            if (this.activeToasts.length >= this.settings.maxToasts) {
                // Remove the oldest toast
                this.activeToasts[0].remove();
                this.activeToasts.shift();
            }

            // Create toast
            const toast = this.createToast(options);

            // Add to container
            this.container.appendChild(toast.element);

            // Show toast (setTimeout needed for animation)
            setTimeout(() => {
                toast.element.classList.add('show');
            }, 10);

            // Add to active toasts
            this.activeToasts.push(toast);

            // Auto-remove toast after duration
            toast.timeoutId = setTimeout(() => {
                this.close(toast.id);
            }, options.duration || this.settings.duration);

            // Return toast instance
            return toast;
        }

        /**
         * Create a toast element
         * @param {Object} options - Toast options
         * @returns {Object} Toast object
         */
        createToast(options) {
            // Toast ID
            const id = 'toast-' + this.toastCount++;

            // Create toast element
            const toastElement = document.createElement('div');
            toastElement.className = `toast ${options.type || 'info'}`;
            toastElement.id = id;

            // Toast content
            let iconClass = '';
            switch (options.type) {
                case 'success':
                    iconClass = 'fa-check';
                    break;
                case 'error':
                    iconClass = 'fa-times';
                    break;
                case 'warning':
                    iconClass = 'fa-exclamation';
                    break;
                default: // info
                    iconClass = 'fa-info';
            }

            toastElement.innerHTML = `
                <div class="toast-icon">
                    <i class="fas ${iconClass}"></i>
                </div>
                <div class="toast-content">
                    <div class="toast-title">${options.title || 'Notification'}</div>
                    ${options.message ? `<div class="toast-message">${options.message}</div>` : ''}
                </div>
                <button class="toast-close" aria-label="Close">
                    <i class="fas fa-times"></i>
                </button>
                <div class="toast-progress">
                    <div class="toast-progress-bar" style="animation: progress ${options.duration || this.settings.duration}ms linear forwards;"></div>
                </div>
            `;

            // Toast object
            const toast = {
                id,
                element: toastElement,
                timeoutId: null,
                remove: () => this.close(id),
                update: (newOptions) => this.update(id, newOptions)
            };

            // Add event listeners
            const closeButton = toastElement.querySelector('.toast-close');
            closeButton.addEventListener('click', () => this.close(id));

            // Pause on hover
            if (this.settings.pauseOnHover) {
                toastElement.addEventListener('mouseenter', () => {
                    clearTimeout(toast.timeoutId);
                    // Pause progress bar animation
                    const progressBar = toastElement.querySelector('.toast-progress-bar');
                    const computedStyle = window.getComputedStyle(progressBar);
                    const width = computedStyle.getPropertyValue('width');
                    progressBar.style.animationPlayState = 'paused';
                    progressBar.style.width = width;
                });

                toastElement.addEventListener('mouseleave', () => {
                    // Get remaining time based on progress width
                    const progressBar = toastElement.querySelector('.toast-progress-bar');
                    const computedStyle = window.getComputedStyle(progressBar);
                    const width = parseFloat(computedStyle.getPropertyValue('width'));
                    const totalWidth = parseFloat(window.getComputedStyle(toastElement.querySelector('.toast-progress')).getPropertyValue('width'));
                    const remainingTime = (width / totalWidth) * (options.duration || this.settings.duration);

                    // Resume countdown
                    toast.timeoutId = setTimeout(() => {
                        this.close(id);
                    }, remainingTime);

                    // Resume progress animation
                    progressBar.style.animation = `progress ${remainingTime}ms linear forwards`;
                    progressBar.style.animationPlayState = 'running';
                });
            }

            return toast;
        }

        /**
         * Close a toast notification
         * @param {string} id - Toast ID
         */
        close(id) {
            // Find toast by ID
            const index = this.activeToasts.findIndex(toast => toast.id === id);
            if (index === -1) return;

            const toast = this.activeToasts[index];

            // Clear timeout
            clearTimeout(toast.timeoutId);

            // Hide toast
            toast.element.classList.remove('show');

            // Remove toast after animation
            setTimeout(() => {
                if (toast.element.parentNode) {
                    toast.element.parentNode.removeChild(toast.element);
                }
                this.activeToasts.splice(index, 1);
            }, 300);
        }

        /**
         * Update a toast notification
         * @param {string} id - Toast ID
         * @param {Object} options - New options
         */
        update(id, options) {
            // Find toast by ID
            const toast = this.activeToasts.find(toast => toast.id === id);
            if (!toast) return;

            // Update title and message if provided
            if (options.title) {
                toast.element.querySelector('.toast-title').textContent = options.title;
            }

            if (options.message) {
                let messageElement = toast.element.querySelector('.toast-message');
                if (messageElement) {
                    messageElement.textContent = options.message;
                } else {
                    messageElement = document.createElement('div');
                    messageElement.className = 'toast-message';
                    messageElement.textContent = options.message;
                    toast.element.querySelector('.toast-content').appendChild(messageElement);
                }
            }

            // Update type if provided
            if (options.type) {
                const currentType = toast.element.className.replace('toast ', '').replace(' show', '');
                toast.element.classList.remove(currentType);
                toast.element.classList.add(options.type);

                // Update icon
                let iconClass = '';
                switch (options.type) {
                    case 'success':
                        iconClass = 'fa-check';
                        break;
                    case 'error':
                        iconClass = 'fa-times';
                        break;
                    case 'warning':
                        iconClass = 'fa-exclamation';
                        break;
                    default: // info
                        iconClass = 'fa-info';
                }

                const iconElement = toast.element.querySelector('.toast-icon i');
                iconElement.className = `fas ${iconClass}`;
            }

            // Reset timeout if duration is provided
            if (options.duration) {
                clearTimeout(toast.timeoutId);

                // Reset progress animation
                const progressBar = toast.element.querySelector('.toast-progress-bar');
                progressBar.style.animation = 'none';
                // Force reflow
                progressBar.offsetHeight;
                progressBar.style.animation = `progress ${options.duration}ms linear forwards`;

                // Set new timeout
                toast.timeoutId = setTimeout(() => {
                    this.close(toast.id);
                }, options.duration);
            }
        }

        /**
         * Helper method to show a success toast
         * @param {string} title - Toast title
         * @param {string} message - Toast message
         * @param {Object} options - Additional options
         */
        success(title, message, options = {}) {
            return this.show({
                title,
                message,
                type: 'success',
                ...options
            });
        }

        /**
         * Helper method to show an error toast
         * @param {string} title - Toast title
         * @param {string} message - Toast message
         * @param {Object} options - Additional options
         */
        error(title, message, options = {}) {
            return this.show({
                title,
                message,
                type: 'error',
                ...options
            });
        }

        /**
         * Helper method to show a warning toast
         * @param {string} title - Toast title
         * @param {string} message - Toast message
         * @param {Object} options - Additional options
         */
        warning(title, message, options = {}) {
            return this.show({
                title,
                message,
                type: 'warning',
                ...options
            });
        }

        /**
         * Helper method to show an info toast
         * @param {string} title - Toast title
         * @param {string} message - Toast message
         * @param {Object} options - Additional options
         */
        info(title, message, options = {}) {
            return this.show({
                title,
                message,
                type: 'info',
                ...options
            });
        }

        /**
         * Clear all toasts
         */
        clearAll() {
            this.activeToasts.forEach(toast => {
                clearTimeout(toast.timeoutId);
                toast.element.classList.remove('show');
            });

            setTimeout(() => {
                this.activeToasts.forEach(toast => {
                    if (toast.element.parentNode) {
                        toast.element.parentNode.removeChild(toast.element);
                    }
                });
                this.activeToasts = [];
            }, 300);
        }
    }

    // Create a global instance
    window.toastify = new ToastNotification();
})();

// Examples of usage:
// toastify.success('Success', 'Operation completed successfully');
// toastify.error('Error', 'Failed to process request');
// toastify.warning('Warning', 'This action cannot be undone');
// toastify.info('Information', 'Your session will expire in 5 minutes');

// Custom toast:
// toastify.show({
//     title: 'Custom Toast',
//     message: 'This is a custom toast notification',
//     type: 'info',
//     duration: 8000
// });
