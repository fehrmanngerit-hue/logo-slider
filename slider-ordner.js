(function() {
    // Ordner-ID aus FileBird. Auf der Seite überschreibbar mit <div id="logoTrack" data-ordner="42">
    var CONFIG = { ordnerId: 42 };
    
    function shuffleArray(array) {
        var shuffled = array.slice();
        for (var i = shuffled.length - 1; i > 0; i--) {
            var j = Math.floor(Math.random() * (i + 1));
            var temp = shuffled[i];
            shuffled[i] = shuffled[j];
            shuffled[j] = temp;
        }
        return shuffled;
    }
    
    function createLogoElement(logo) {
        var logoItem = document.createElement('div');
        logoItem.className = 'logo-item';
        var img = document.createElement('img');
        img.src = logo.url;
        img.alt = logo.name;
        img.loading = 'lazy';
        logoItem.appendChild(img);
        return logoItem;
    }
    
    function loadLogos(ordnerId) {
        return fetch('/wp-json/mds-logo-slider/v1/ordner/' + ordnerId)
            .then(function(response) {
                if (!response.ok) {
                    throw new Error('API Fehler ' + response.status);
                }
                return response.json();
            })
            .catch(function(error) {
                console.error('Fehler beim Laden:', error);
                return [];
            });
    }
    
    function initSlider() {
        var track = document.getElementById('logoTrack');
        if (!track) {
            console.error('logoTrack Element nicht gefunden');
            return;
        }
        
        var ordnerId = track.getAttribute('data-ordner') || CONFIG.ordnerId;
        
        loadLogos(ordnerId).then(function(logos) {
            if (!logos || logos.length === 0) {
                console.log('Keine Logos im FileBird-Ordner ' + ordnerId + ' gefunden');
                return;
            }
            
            var shuffled = shuffleArray(logos);
            
            for (var i = 0; i < shuffled.length; i++) {
                track.appendChild(createLogoElement(shuffled[i]));
            }
            for (var i = 0; i < shuffled.length; i++) {
                track.appendChild(createLogoElement(shuffled[i]));
            }
            
            var duration = Math.max(30, logos.length * 2.0);
            track.style.animationDuration = duration + 's';
        });
    }
    
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initSlider);
    } else {
        initSlider();
    }
})();
