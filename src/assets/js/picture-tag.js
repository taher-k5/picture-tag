/**
 * Picture Tag Plugin - JavaScript
 * Handles lazy loading, intersection observer, and other interactive features
 */

class PictureTag {
    constructor(options = {}) {
        this.options = {
            rootMargin: '50px 0px',
            threshold: 0.01,
            loadingClass: 'lazy',
            loadedClass: 'loaded',
            fadeInDuration: 300,
            ...options
        };
        
        this.observer = null;
        this.init();
    }

    init() {
        // Initialize intersection observer for lazy loading
        this.initIntersectionObserver();
        
        // Handle existing lazy images
        this.loadVisibleImages();
        
        // Handle dynamic content
        this.handleDynamicContent();
        
        // Initialize error handling
        this.initErrorHandling();
    }

    initIntersectionObserver() {
        if (!('IntersectionObserver' in window)) {
            // Fallback for browsers without IntersectionObserver
            this.loadAllImages();
            return;
        }

        this.observer = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    this.loadImage(entry.target);
                }
            });
        }, {
            rootMargin: this.options.rootMargin,
            threshold: this.options.threshold
        });
    }

    loadVisibleImages() {
        const lazyImages = document.querySelectorAll(`img.${this.options.loadingClass}`);
        
        lazyImages.forEach(img => {
            if (this.isImageVisible(img)) {
                this.loadImage(img);
            } else if (this.observer) {
                this.observer.observe(img);
            }
        });
    }

    isImageVisible(img) {
        const rect = img.getBoundingClientRect();
        const windowHeight = window.innerHeight || document.documentElement.clientHeight;
        const windowWidth = window.innerWidth || document.documentElement.clientWidth;
        
        return (
            rect.top < windowHeight + parseInt(this.options.rootMargin.split(' ')[0]) &&
            rect.bottom > -parseInt(this.options.rootMargin.split(' ')[0]) &&
            rect.left < windowWidth &&
            rect.right > 0
        );
    }

    loadImage(img) {
        if (img.dataset.src) {
            // Create a new image to preload
            const tempImg = new Image();
            
            tempImg.onload = () => {
                img.src = tempImg.src;
                img.classList.add(this.options.loadedClass);
                img.classList.remove(this.options.loadingClass);
                
                // Fade in effect
                if (this.options.fadeInDuration > 0) {
                    img.style.transition = `opacity ${this.options.fadeInDuration}ms ease-in-out`;
                    img.style.opacity = '0';
                    
                    requestAnimationFrame(() => {
                        img.style.opacity = '1';
                    });
                }
                
                // Remove data-src
                delete img.dataset.src;
                
                // Stop observing this image
                if (this.observer) {
                    this.observer.unobserve(img);
                }
                
                // Trigger custom event
                img.dispatchEvent(new CustomEvent('pictureLoaded', {
                    detail: { img: img }
                }));
            };
            
            tempImg.onerror = () => {
                this.handleImageError(img);
            };
            
            tempImg.src = img.dataset.src;
        }
    }

    loadAllImages() {
        const lazyImages = document.querySelectorAll(`img.${this.options.loadingClass}`);
        
        lazyImages.forEach(img => {
            this.loadImage(img);
        });
    }

    handleImageError(img) {
        img.classList.add('picture-error');
        img.classList.remove(this.options.loadingClass);
        
        // Trigger error event
        img.dispatchEvent(new CustomEvent('pictureError', {
            detail: { img: img }
        }));
    }

    initErrorHandling() {
        document.addEventListener('error', (e) => {
            if (e.target.tagName === 'IMG') {
                this.handleImageError(e.target);
            }
        }, true);
    }

    handleDynamicContent() {
        // Handle dynamically added content
        if (typeof MutationObserver !== 'undefined') {
            const mutationObserver = new MutationObserver((mutations) => {
                mutations.forEach((mutation) => {
                    mutation.addedNodes.forEach((node) => {
                        if (node.nodeType === 1) { // Element node
                            // Check if the added node is a lazy image
                            if (node.tagName === 'IMG' && node.classList.contains(this.options.loadingClass)) {
                                if (this.isImageVisible(node)) {
                                    this.loadImage(node);
                                } else if (this.observer) {
                                    this.observer.observe(node);
                                }
                            }
                            
                            // Check for lazy images within the added node
                            const lazyImages = node.querySelectorAll(`img.${this.options.loadingClass}`);
                            lazyImages.forEach(img => {
                                if (this.isImageVisible(img)) {
                                    this.loadImage(img);
                                } else if (this.observer) {
                                    this.observer.observe(img);
                                }
                            });
                        }
                    });
                });
            });
            
            mutationObserver.observe(document.body, {
                childList: true,
                subtree: true
            });
        }
    }

    // Public methods
    refresh() {
        this.loadVisibleImages();
    }

    destroy() {
        if (this.observer) {
            this.observer.disconnect();
        }
    }

    // Utility methods
    static supportsWebP() {
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        return canvas.toDataURL('image/webp').indexOf('data:image/webp') === 0;
    }

    static supportsAvif() {
        const canvas = document.createElement('canvas');
        canvas.width = 1;
        canvas.height = 1;
        return canvas.toDataURL('image/avif').indexOf('data:image/avif') === 0;
    }

    static getImageSize(img) {
        return new Promise((resolve) => {
            if (img.naturalWidth && img.naturalHeight) {
                resolve({
                    width: img.naturalWidth,
                    height: img.naturalHeight
                });
            } else {
                const tempImg = new Image();
                tempImg.onload = () => {
                    resolve({
                        width: tempImg.naturalWidth,
                        height: tempImg.naturalHeight
                    });
                };
                tempImg.src = img.src;
            }
        });
    }
}

// Picture Gallery functionality
class PictureGallery {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            lightbox: true,
            swipe: true,
            keyboard: true,
            ...options
        };
        
        this.init();
    }

    init() {
        this.setupGallery();
        
        if (this.options.lightbox) {
            this.initLightbox();
        }
        
        if (this.options.swipe) {
            this.initSwipe();
        }
        
        if (this.options.keyboard) {
            this.initKeyboard();
        }
    }

    setupGallery() {
        const images = this.container.querySelectorAll('img');
        
        images.forEach((img, index) => {
            img.addEventListener('click', () => {
                this.openLightbox(index);
            });
            
            img.style.cursor = 'pointer';
        });
    }

    initLightbox() {
        this.lightbox = document.createElement('div');
        this.lightbox.className = 'picture-lightbox';
        this.lightbox.innerHTML = `
            <div class="picture-lightbox-overlay"></div>
            <div class="picture-lightbox-content">
                <button class="picture-lightbox-close">&times;</button>
                <button class="picture-lightbox-prev">&larr;</button>
                <button class="picture-lightbox-next">&rarr;</button>
                <img class="picture-lightbox-image">
            </div>
        `;
        
        document.body.appendChild(this.lightbox);
        
        // Event listeners
        this.lightbox.querySelector('.picture-lightbox-close').addEventListener('click', () => {
            this.closeLightbox();
        });
        
        this.lightbox.querySelector('.picture-lightbox-overlay').addEventListener('click', () => {
            this.closeLightbox();
        });
        
        this.lightbox.querySelector('.picture-lightbox-prev').addEventListener('click', () => {
            this.previousImage();
        });
        
        this.lightbox.querySelector('.picture-lightbox-next').addEventListener('click', () => {
            this.nextImage();
        });
    }

    openLightbox(index) {
        this.currentIndex = index;
        this.lightbox.classList.add('active');
        this.updateLightboxImage();
    }

    closeLightbox() {
        this.lightbox.classList.remove('active');
    }

    updateLightboxImage() {
        const images = this.container.querySelectorAll('img');
        const lightboxImg = this.lightbox.querySelector('.picture-lightbox-image');
        
        if (images[this.currentIndex]) {
            lightboxImg.src = images[this.currentIndex].src;
            lightboxImg.alt = images[this.currentIndex].alt;
        }
    }

    previousImage() {
        const images = this.container.querySelectorAll('img');
        this.currentIndex = this.currentIndex > 0 ? this.currentIndex - 1 : images.length - 1;
        this.updateLightboxImage();
    }

    nextImage() {
        const images = this.container.querySelectorAll('img');
        this.currentIndex = this.currentIndex < images.length - 1 ? this.currentIndex + 1 : 0;
        this.updateLightboxImage();
    }

    initSwipe() {
        let startX = 0;
        let endX = 0;
        
        this.lightbox.addEventListener('touchstart', (e) => {
            startX = e.touches[0].clientX;
        });
        
        this.lightbox.addEventListener('touchend', (e) => {
            endX = e.changedTouches[0].clientX;
            this.handleSwipe(startX, endX);
        });
    }

    handleSwipe(startX, endX) {
        const threshold = 50;
        const diff = startX - endX;
        
        if (Math.abs(diff) > threshold) {
            if (diff > 0) {
                this.nextImage();
            } else {
                this.previousImage();
            }
        }
    }

    initKeyboard() {
        document.addEventListener('keydown', (e) => {
            if (this.lightbox.classList.contains('active')) {
                switch (e.key) {
                    case 'Escape':
                        this.closeLightbox();
                        break;
                    case 'ArrowLeft':
                        this.previousImage();
                        break;
                    case 'ArrowRight':
                        this.nextImage();
                        break;
                }
            }
        });
    }
}

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    // Initialize PictureTag
    window.pictureTag = new PictureTag();
    
    // Initialize galleries
    const galleries = document.querySelectorAll('.picture-gallery');
    galleries.forEach(gallery => {
        new PictureGallery(gallery);
    });
});

// Export for module systems
if (typeof module !== 'undefined' && module.exports) {
    module.exports = { PictureTag, PictureGallery };
}
