// Sales form JavaScript functions
let idx = 0;

// Search function for both paintings and frames
function filterItems(query, idx) {
    const suggestions = document.getElementById(`item-suggestions-${idx}`);

    if (!query || query.length < 1) {
        suggestions.classList.add('hidden');
        return;
    }

    // Get routes from window object (set in blade template)
    const paintingsRoute = window.salesRoutes.searchPaintings;
    const framesRoute = window.salesRoutes.searchFrames;

    // Fetch both paintings and frames
    Promise.all([
        fetch(`${paintingsRoute}?q=${encodeURIComponent(query)}`).then(r => r.json()),
        fetch(`${framesRoute}?q=${encodeURIComponent(query)}`).then(r => r.json())
    ])
        .then(([paintings, frames]) => {
            let html = '';

            // Add paintings section
            if (paintings.length > 0) {
                html += '<div class="px-3 py-1 bg-gray-100 text-xs font-bold text-gray-600">TRANH</div>';
                html += paintings.map(p => `
                <div class="px-3 py-2 hover:bg-blue-50 cursor-pointer border-b" onclick="selectPainting(${p.id}, ${idx})">
                    <div class="font-medium text-sm">${p.code} - ${p.name}</div>
                    <div class="text-xs text-gray-500">USD: ${p.price_usd || 0} | VND: ${(p.price_vnd || 0).toLocaleString()}đ</div>
                </div>
            `).join('');
            }

            // Add frames section
            if (frames.length > 0) {
                html += '<div class="px-3 py-1 bg-gray-100 text-xs font-bold text-gray-600">KHUNG</div>';
                html += frames.map(f => `
                <div class="px-3 py-2 hover:bg-green-50 cursor-pointer border-b" onclick="selectFrame(${f.id}, ${idx})">
                    <div class="font-medium text-sm">${f.name}</div>
                    <div class="text-xs text-gray-500">USD: ${f.cost_price_usd || 0} | VND: ${(f.cost_price || 0).toLocaleString()}đ</div>
                </div>
            `).join('');
            }

            if (html) {
                suggestions.innerHTML = html;
                suggestions.classList.remove('hidden');
            } else {
                suggestions.classList.add('hidden');
            }
        })
        .catch(error => {
            console.error('Error searching items:', error);
            suggestions.classList.add('hidden');
        });
}

function showItemSuggestions(idx) {
    const input = document.getElementById(`item-search-${idx}`);

    if (input && input.value.length >= 1) {
        filterItems(input.value, idx);
    }
}

function selectPainting(paintingId, idx) {
    const paintingRoute = window.salesRoutes.getPainting.replace(':id', paintingId);

    fetch(paintingRoute)
        .then(response => response.json())
        .then(painting => {
            // Clear frame selection
            document.getElementById(`frame-id-${idx}`).value = '';

            // Set painting data
            document.getElementById(`painting-id-${idx}`).value = painting.id;
            document.getElementById(`item-search-${idx}`).value = `${painting.code} - ${painting.name}`;
            document.getElementById(`desc-${idx}`).value = painting.name;

            const usdInput = document.querySelector(`.usd-${idx}`);
            const vndInput = document.querySelector(`.vnd-${idx}`);

            if (usdInput) {
                const usdValue = parseFloat(painting.price_usd) || 0;
                usdInput.value = usdValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }
            if (vndInput) {
                const vndValue = parseInt(painting.price_vnd) || 0;
                vndInput.value = vndValue.toLocaleString('en-US');
            }

            const imgUrl = painting.image ? `/storage/${painting.image}` : '/images/no-image.svg';
            const imgElement = document.getElementById(`img-${idx}`);
            imgElement.src = imgUrl;
            if (typeof showImageModal === 'function') {
                imgElement.onclick = () => showImageModal(imgUrl, painting.name);
            }
            imgElement.classList.add('cursor-pointer', 'hover:opacity-80', 'transition-opacity');

            document.getElementById(`item-suggestions-${idx}`).classList.add('hidden');
            if (typeof calc === 'function') calc();
        })
        .catch(error => {
            console.error('Error fetching painting:', error);
        });
}

function selectFrame(frameId, idx) {
    const frameRoute = window.salesRoutes.getFrame.replace(':id', frameId);

    fetch(frameRoute)
        .then(response => response.json())
        .then(frame => {
            // Clear painting selection
            document.getElementById(`painting-id-${idx}`).value = '';

            // Set frame data
            document.getElementById(`frame-id-${idx}`).value = frame.id;
            document.getElementById(`item-search-${idx}`).value = frame.name;
            document.getElementById(`desc-${idx}`).value = frame.name;

            // Set price from frame cost_price
            const vndInput = document.querySelector(`.vnd-${idx}`);
            const usdInput = document.querySelector(`.usd-${idx}`);
            
            if (vndInput) {
                const vndValue = parseInt(frame.cost_price) || 0;
                vndInput.value = vndValue.toLocaleString('en-US');
            }
            
            if (usdInput) {
                const usdValue = parseFloat(frame.cost_price_usd) || 0;
                usdInput.value = usdValue.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
            }

            // Clear image for frame
            const imgElement = document.getElementById(`img-${idx}`);
            imgElement.src = '/images/frame-placeholder.svg';

            document.getElementById(`item-suggestions-${idx}`).classList.add('hidden');
            if (typeof calc === 'function') calc();
        })
        .catch(error => {
            console.error('Error fetching frame:', error);
        });
}
