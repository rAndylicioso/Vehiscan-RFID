// Carousel functionality for guard panel
class HomeownerCarousel {
    constructor() {
        // Elements
        this.ownerImage = document.getElementById('ownerImage');
        this.carImage = document.getElementById('carImage');
        this.ownerName = document.getElementById('ownerName');
        this.ownerAddress = document.getElementById('ownerAddress');
        this.ownerContact = document.getElementById('ownerContact');
        this.vehicleType = document.getElementById('vehicleType');
        this.vehicleColor = document.getElementById('vehicleColor');
        this.plateNumber = document.getElementById('plateNumber');
        this.prevBtn = document.getElementById('prevOwner');
        this.nextBtn = document.getElementById('nextOwner');
        this.ownerCounter = document.getElementById('ownerCounter');
        this.searchInput = document.getElementById('homeownerSearch');
        this.clearSearch = document.getElementById('clearSearch');

        // State
        this.homeowners = [];
        this.currentIndex = 0;
        this.isNavigating = false;
        this.lastNavigationTime = 0;

        // Constants
        this.DEBOUNCE_TIME = 300;
        this.PLACEHOLDER_CAR = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="200"%3E%3Crect fill="%23ddd" width="400" height="200"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999" font-size="18" font-family="Arial"%3ENo Vehicle%3C/text%3E%3C/svg%3E';
        this.PLACEHOLDER_OWNER = 'data:image/svg+xml,%3Csvg xmlns="http://www.w3.org/2000/svg" width="400" height="200"%3E%3Crect fill="%23ddd" width="400" height="200"/%3E%3Ctext x="50%25" y="50%25" text-anchor="middle" dy=".3em" fill="%23999" font-size="18" font-family="Arial"%3ENo Owner%3C/text%3E%3C/svg%3E';

        // Initialize
        this.init();
    }

    init() {
        // Use global logger provided by `logger.js`
        // Set default placeholders
        this.ownerImage.src = this.PLACEHOLDER_OWNER;
        this.carImage.src = this.PLACEHOLDER_CAR;

        // Bind event listeners with explicit handlers
        const handlePrev = (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.navigate('prev');
        };
        
        const handleNext = (e) => {
            e.preventDefault();
            e.stopPropagation();
            this.navigate('next');
        };

        this.prevBtn.addEventListener('click', handlePrev);
        this.prevBtn.addEventListener('touchstart', handlePrev);
        this.nextBtn.addEventListener('click', handleNext);
        this.nextBtn.addEventListener('touchstart', handleNext);
        this.clearSearch.addEventListener('click', () => this.handleClearSearch());
        this.searchInput.addEventListener('input', (e) => this.handleSearch(e));

        // Add keyboard navigation
        document.addEventListener('keydown', (e) => {
            if (e.key === 'ArrowLeft') this.navigate('prev');
            if (e.key === 'ArrowRight') this.navigate('next');
        });

        // Initial load
        this.loadHomeowners();
    }

    async loadHomeowners() {
        try {
            // Get the base URL from the global config
            const baseUrl = window.vehiscanConfig?.baseUrl || '';
            const response = await fetch(`${baseUrl}/guard/pages/fetch_homeowners.php`);
            
            if (!response.ok) {
                throw new Error(`Failed to load homeowners: ${response.status}`);
            }

            const data = await response.json();
            if (!Array.isArray(data)) {
                throw new Error('Invalid data received: expected array');
            }

            this.homeowners = data;
            this.currentIndex = 0;
            
            if (this.homeowners.length > 0) {
                await this.displayCurrentHomeowner();
                this.updateNavigationButtons();
                __vsLog(`Loaded ${this.homeowners.length} homeowners`);
            } else {
                __vsLog('No homeowners found');
            }
            
            // Update counter display
            this.ownerCounter.textContent = `${this.currentIndex + 1}/${this.homeowners.length}`;
            
        } catch (error) {
            console.error('[Carousel] Load error:', error);
            // Show error to user
            this.showError('Failed to load homeowners. Please refresh the page.');
        }
    }

    showError(message) {
        if (window.Swal) {
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: message,
                confirmButtonColor: getComputedStyle(document.documentElement).getPropertyValue('--guard-accent') || '#3498db'
            });
        } else {
            alert(message);
        }
    }

    async navigate(direction) {
        if (this.isNavigating || this.homeowners.length === 0) return;
        
        const now = Date.now();
        if (now - this.lastNavigationTime < this.DEBOUNCE_TIME) return;
        
        this.isNavigating = true;
        this.lastNavigationTime = now;

        try {
            // Calculate new index
            const currentId = this.homeowners[this.currentIndex]?.id;
            
            if (direction === 'next') {
                this.currentIndex = (this.currentIndex + 1) % this.homeowners.length;
            } else {
                this.currentIndex = (this.currentIndex - 1 + this.homeowners.length) % this.homeowners.length;
            }

            // Ensure we're not showing the same homeowner
            if (this.homeowners[this.currentIndex]?.id === currentId) {
                if (direction === 'next') {
                    this.currentIndex = (this.currentIndex + 1) % this.homeowners.length;
                } else {
                    this.currentIndex = (this.currentIndex - 1 + this.homeowners.length) % this.homeowners.length;
                }
            }

            await this.displayCurrentHomeowner();
        } finally {
            this.isNavigating = false;
        }
    }

    async displayCurrentHomeowner(skipAnimation = false) {
        const homeowner = this.homeowners[this.currentIndex];
        if (!homeowner) return;

        // Save state
        localStorage.setItem('lastHomeownerId', homeowner.id.toString());
        localStorage.setItem('lastHomeownerIndex', this.currentIndex.toString());

        // Update counter
        this.ownerCounter.textContent = `${this.currentIndex + 1}/${this.homeowners.length}`;

        // Update text content
        this.ownerName.textContent = `Name: ${homeowner.name || '-'}`;
        this.ownerAddress.textContent = `Address: ${homeowner.address || '-'}`;
        this.ownerContact.textContent = `Contact: ${homeowner.contact || '-'}`;
        this.vehicleType.textContent = `Vehicle Type: ${homeowner.vehicle_type || '-'}`;
        this.vehicleColor.textContent = `Color: ${homeowner.color || '-'}`;
        this.plateNumber.textContent = `Plate Number: ${homeowner.plate_number || '-'}`;

        // Handle animation
        if (!skipAnimation) {
            const container = document.querySelector('.homeowner-details-container');
            if (container) {
                container.style.animation = 'none';
                container.offsetHeight; // Trigger reflow
                container.style.animation = 'fadeInRight 0.3s ease-out';
            }
        }

        // Load images
    // Prefer server-provided absolute URLs if available
    await this.loadImage(this.ownerImage, homeowner.owner_img_url || homeowner.owner_img, this.PLACEHOLDER_OWNER);
    await this.loadImage(this.carImage, homeowner.car_img_url || homeowner.car_img, this.PLACEHOLDER_CAR);
    }

    async loadImage(imageElement, src, placeholder) {
        if (!src) {
            imageElement.src = placeholder;
            return;
        }

        try {
            // Build robust URL like guard_side.tryLoadImage: accept full URLs, site-relative, or filenames
            const buildImageUrl = (rawPath, kind) => {
                if (!rawPath) return null;
                if (/^https?:\/\//i.test(rawPath)) return rawPath;
                let p = rawPath.replace(/^\/+/, '');
                if (/^(uploads\/)/i.test(p)) {
                    // ok
                } else if (/^vehicles\//i.test(p)) {
                    p = 'uploads/' + p;
                } else if (/^homeowners\//i.test(p)) {
                    p = 'uploads/' + p;
                } else {
                    // assume vehicle image when using carousel
                    p = 'uploads/vehicles/' + p;
                }

                let base = window.vehiscanConfig?.baseUrl || window.baseUrl || '';
                const origin = window.location.origin;
                if (!base) base = origin;
                else if (base.startsWith('//')) base = window.location.protocol + base;
                else if (base.startsWith('/')) base = origin + base;
                else if (!/^https?:\/\//i.test(base)) base = origin + '/' + base.replace(/^\/+/, '');

                return base.replace(/\/$/, '') + '/' + p.replace(/^\/+/, '');
            };

            const imageUrl = buildImageUrl(src, 'vehicle');

            const response = await fetch(imageUrl, { method: 'HEAD' });
            if (response.ok) {
                imageElement.src = imageUrl;
            } else {
                imageElement.src = placeholder;
            }
        } catch (error) {
            console.error('[Carousel] Image load error:', error);
            imageElement.src = placeholder;
        }
    }

    updateNavigationButtons() {
        const hasHomeowners = this.homeowners.length > 0;
        this.prevBtn.disabled = !hasHomeowners;
        this.nextBtn.disabled = !hasHomeowners;
    }

    handleClearSearch() {
        this.searchInput.value = '';
        if (this.homeowners.length > 0) {
            this.displayCurrentHomeowner();
        }
    }

    handleSearch(event) {
        const query = event.target.value.toLowerCase().trim();
        
        // If query is empty, show all homeowners
        if (!query) {
            this.loadHomeowners();
            return;
        }

        try {
            // Filter homeowners based on search query
            const filteredHomeowners = this.homeowners.filter(h => 
                (h.name && h.name.toLowerCase().includes(query)) ||
                (h.plate_number && h.plate_number.toLowerCase().includes(query)) ||
                (h.address && h.address.toLowerCase().includes(query))
            );

            if (filteredHomeowners.length > 0) {
                // Update the display with the first match
                const foundIndex = this.homeowners.findIndex(h => h.id === filteredHomeowners[0].id);
                if (foundIndex !== -1) {
                    this.currentIndex = foundIndex;
                    this.displayCurrentHomeowner();
                    __vsLog('Found match:', this.homeowners[this.currentIndex].name);
                }
            } else {
                __vsLog('No matches found for:', query);
            }
        } catch (error) {
            console.error('Search error:', error);
        }
    }
}

// Note: Carousel class is defined here. Initialization is handled by the page (guard_side.js)