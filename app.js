function initMap() {
    let map = new google.maps.Map(document.getElementById('map'), {
        zoom: 12,
        center: { lat: 17.3850, lng: 78.4867 } // default Hyderabad
    });

    let foodItems = document.querySelectorAll('#foodList li');
    foodItems.forEach(item => {
        let lat = parseFloat(item.dataset.lat);
        let lng = parseFloat(item.dataset.lng);
        new google.maps.Marker({
            position: { lat: lat, lng: lng },
            map: map,
            title: item.textContent
        });
    });
}
