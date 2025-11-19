document.addEventListener('DOMContentLoaded', () => {
    const mapElements = document.querySelectorAll('.order-map');

    const customIcon = L.divIcon({
        className: 'custom-map-icon',
        html: '<i class="bi bi-geo-alt-fill" style="color:#ffd700; font-size: 24px; text-shadow: -1px -1px 0 #000, 1px -1px 0 #000, -1px 1px 0 #000, 1px 1px 0 #000;"></i>',
        iconSize: [24, 24],
        iconAnchor: [12, 24]
    });

    mapElements.forEach(mapEl => {
        const deliveryLat = parseFloat(mapEl.dataset.deliveryLat);
        const deliveryLng = parseFloat(mapEl.dataset.deliveryLng);
        const vendorLat = parseFloat(mapEl.dataset.vendorLat);
        const vendorLng = parseFloat(mapEl.dataset.vendorLng);
        const vendorName = mapEl.dataset.vendorName;

        const deliveryCoords = L.latLng(deliveryLat, deliveryLng);
        const vendorCoords = L.latLng(vendorLat, vendorLng);

        const mapOptions = {
            dragging: false,
            zoomControl: false,
            scrollWheelZoom: false,
            doubleClickZoom: false,
            boxZoom: false,
            keyboard: false,
            touchZoom: false
        };

        const map = L.map(mapEl.id, mapOptions).setView(deliveryCoords, 12);

        // CartoDB Positron (Modern Light Style)
        L.tileLayer('https://{s}.basemaps.cartocdn.com/light_all/{z}/{x}/{y}{r}.png', {
            maxZoom: 19,
            attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors &copy; <a href="https://carto.com/attributions">CARTO</a>'
        }).addTo(map);

        const routingControl = L.Routing.control({
            waypoints: [
                vendorCoords,
                deliveryCoords
            ],
            routeWhileDragging: false,
            show: false,
            createMarker: function() { return null; },
            lineOptions: {
                styles: [{
                    color: '#ffd700',
                    weight: 6,
                    opacity: 1
                }]
            },
            fitSelectedRoutes: false
        }).addTo(map);

        // Vendor Marker: Smaller, always visible, new label
        const vendorPopupContent = `<div style="font-size: 0.8em;"><b>Store:</b> ${vendorName}</div>`;
        L.marker(vendorCoords, { icon: customIcon }).addTo(map)
            .bindPopup(vendorPopupContent, { closeButton: false })
            .openPopup();

        // Delivery Marker: Smaller, always visible
        const deliveryPopupContent = `<div style="font-size: 0.8em;"><b>Your Delivery Address</b></div>`;
        L.marker(deliveryCoords, { icon: customIcon }).addTo(map)
            .bindPopup(deliveryPopupContent, { closeButton: false })
            .openPopup();

        const bounds = L.latLngBounds(vendorCoords, deliveryCoords);
        map.fitBounds(bounds, { padding: [50, 50] });
    });
});