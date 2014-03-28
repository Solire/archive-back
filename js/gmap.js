function addMarker(map3, lat, lng) {
    map3.gmap3({
        marker: {
            tag: 'myMarker',
            callback: function(marker) {
                map3.data('marker', marker)
                $('.gmap-marker-button', map3.data('btn-marker')).attr('title', 'Cliquez pour supprimer le marqueur')
                $('.gmap-marker-button div strong', map3.data('btn-marker')).html('Supprimer le marqueur')
                $('input.gmap_lat', map3.parents('.line:first')).val(marker.position.lat());
                $('input.gmap_lng', map3.parents('.line:first')).val(marker.position.lng());
                $('input.gmap_zoom', map3.parents('.line:first')).val(map3.gmap3('get').getZoom());
            },
            latLng: [lat, lng],
            options: {
                draggable: true,
                animation: google.maps.Animation.DROP
            },
            events: {
                dragend: function(marker) {
                    $('input.gmap_lat', map3.parents('.line:first')).val(marker.position.lat());
                    $('input.gmap_lng', map3.parents('.line:first')).val(marker.position.lng());
                }
            }
        }
    })
}

function removeMarker(map3) {
    map3.gmap3({
        clear: {
            callback: function() {
                map3.data('marker', null)
                $('.gmap-marker-button', map3.data('btn-marker')).attr('title', 'Cliquez pour ajouter un marqueur')
                $('.gmap-marker-button div strong', map3.data('btn-marker')).html('Ajouter un marqueur')
                $('input.gmap_lat', map3.parents('.line:first')).val('');
                $('input.gmap_lng', map3.parents('.line:first')).val('');
                $('input.gmap_zoom', map3.parents('.line:first')).val('');
            },
            tag: 'myMarker'
        }

    })
}

$(function(){
    function MarkerControl(controlDiv, map, map3) {
        var chicago = new google.maps.LatLng(41.850033, -87.6500523);

        // Set CSS styles for the DIV containing the control
        // Setting padding to 5 px will offset the control
        // from the edge of the map.
        controlDiv.style.padding = '5px';

        // Set CSS for the control border.
        var controlUI = document.createElement('div');
        controlUI.style.backgroundColor = 'white';
        controlUI.style.borderStyle = 'solid';
        controlUI.style.borderWidth = '2px';
        controlUI.style.cursor = 'pointer';
        controlUI.style.textAlign = 'center';
        controlUI.className = 'gmap-marker-button';
        controlUI.title = 'Cliquez pour ajouter un marqueur au centre de la carte';
        controlDiv.appendChild(controlUI);

        // Set CSS for the control interior.
        var controlText = document.createElement('div');
        controlText.style.fontFamily = 'Arial,sans-serif';
        controlText.style.fontSize = '12px';
        controlText.style.paddingLeft = '4px';
        controlText.style.paddingRight = '4px';
        controlText.innerHTML = '<strong>Ajouter un marqueur</strong>';
        controlUI.appendChild(controlText);

        // Setup the click event listeners: simply set the map to Chicago.
        google.maps.event.addDomListener(controlUI, 'click', function() {
            var ctr = map.getCenter();
            var lat = ctr.lat();
            var lng = ctr.lng();

            if (map3.data('marker')) {
                removeMarker(map3)
            } else {
                addMarker(map3, lat, lng)
            }

        });
    }

    /**
     * Champ de type map
     */
    $('.gmap-component').livequery(function() {
        var $this = $(this)
        $(this).gmap3({
            map: {
                options: {
                    streetViewControl: false
                },
                events: {
                    zoom_changed: function(map) {
                        if ($this.data('marker')) {
                            $('input.gmap_zoom', $this.parents('.line:first')).val(map.getZoom());
                        }
                    }
                },
                callback: function(map) {
                    // Create the DIV to hold the control and call the HomeControl() constructor
                    // passing in this DIV.
                    var markerControlDiv = document.createElement('div');
                    $this.data('btn-marker', markerControlDiv)
                    var homeControl = new MarkerControl(markerControlDiv, map, $this);

                    markerControlDiv.index = 1;
                    map.controls[google.maps.ControlPosition.TOP_RIGHT].push(markerControlDiv);

                    if ($('input.gmap_lat', $this.parents('.line:first')).val() != '' && $('input.gmap_lng', $this.parents('.line:first')).val() != '') {
                        var lat = $('input.gmap_lat', $this.parents('.line:first')).val();
                        var lng = $('input.gmap_lng', $this.parents('.line:first')).val();
                        var zoom = $('input.gmap_zoom', $this.parents('.line:first')).val();
                        map.setZoom(parseInt(zoom))
                        map.setCenter(new google.maps.LatLng(lat, lng))
                        addMarker($this, lat, lng)
                    }
                }
            },
        });
        var map = $(this).gmap3('get')
    });
});

