var app = app || {};

// Models ----------------------------------

app.Playground = Backbone.Model.extend({
    urlRoot: "/api/playgrounds",
    defaults: {
        "id": null,
        "name": "",
        "address": ""
    }
});

app.Playgrounds = Backbone.Collection.extend({
    model: app.Playground,

    url: "/api/playgrounds",

    initialize: function () {
        this.selected = null;
    },

    setSelected: function (playground) {
        this.selected = playground;
        this.trigger('selectPlayground', playground);
    }
});

// Views -----------------------------------
Backbone.View.prototype.close = function () {
    if (this.beforeClose) {
        this.beforeClose();
    }
    this.remove();
    this.unbind();
};

// Handle view transitions and maintain view state across instances.
app.ViewManager = function () {
    _.extend(this, Backbone.Events);

    this.currentLayout = null; // 'compact' or 'full'
    this.currentView = null; // The current view object shown by showView()
    this.compactFindView = 'list'; // 'list' or 'map'

    this.onResize = function () {
        this.trigger('resize');
        this.determineLayout();
    };

    this.determineLayout = function () {
        var prevLayout = this.currentLayout;
        this.currentLayout = $(window).width() <= 768 ? 'compact' : 'full';

        if (this.currentLayout != prevLayout) {
            this.trigger('changeLayout', this.currentLayout);
        }
    };

    this.showView = function (view, selector) {
        if (this.currentView) this.currentView.close();
        this.currentView = view;

        // Hide any view-specific navigation buttons.
        // The view will show whatever navigation it needs when it's rendered.
        $('.view-nav-btn').hide();

        if (selector) {
            $(selector).html(view.render().el);
            if (typeof(view.attachedToDom) == 'function') {
                view.attachedToDom();
            }
        } else {
            view.render();
        }
    };

    this.setCompactFindView = function (viewName) {
        if (viewName != 'map' && viewName != 'list') {
            throw new Error("Attempted to set invalid value for find compact view.");
        }

        this.compactFindView = viewName;
        if (this.currentView.viewName == 'find' && this.currentLayout == 'compact') {
            this.currentView.updateCompactView();
        }

    };
};

app.HeaderView = Backbone.View.extend({
    template: _.template($('#tpl-header').html()),

    events: {
        'click #back-btn': 'clickBackBtn',
        'click #show-list-btn': 'clickListBtn',
        'click #show-map-btn': 'clickMapBtn'
    },

    clickBackBtn: function () {
        this.trigger('clickBackBtn');
    },

    clickListBtn: function () {
        this.trigger('clickListBtn');
    },

    clickMapBtn: function () {
        this.trigger('clickMapBtn');
    },

    render: function () {
        this.$el.html(this.template());
        return this;
    }
});

// The "find" view includes a map and a sidebar with a search form and a list of models.
// In compact layout, only one is shown at a time, either the map or the searchlist (sidebar).
//
// Header navigation: In compact layout, a button is shown in the header to switch between the map and the list.
//
// List items:
// In full layout, clicking an item in the list shows it on the map. A small button is shown to go to the details view.
// In compact layout, the clicking an item in the list opens details. A small button is shown to view the item on the map.
//
// Map info windows:
// In full layout, the map shows info window popups when an item is selected.
// In compact layout, the info window is shown as a fixed div above the map.
//
// Resizing:
// The height of the map div and the searchlist div need to be resized each time the window is resized.
app.FindView = Backbone.View.extend({
    viewName: 'find',

    initialize: function() {
        this.searchFormView = new app.SearchFormView();
        this.listView = new app.ListView({collection: this.collection});
        this.mapView = new app.MapView({collection: this.collection});

        this.listenTo(app.viewman, 'changeLayout', this.updateLayout);
        this.listenTo(app.viewman, 'resize', this.resize);
        this.listenTo(app.headerView, 'clickListBtn', this.clickListBtn);
        this.listenTo(app.headerView, 'clickMapBtn', this.clickMapBtn);

        this.onAttachedToDomListeners = [];
    },

    render: function () {
        this.$searchlist = $('<div/>', {id: 'search-list'});
        this.$searchlist.append(this.searchFormView.render().el);
        this.$searchlist.append(this.listView.render().el);

        this.$el.append(this.$searchlist);
        this.$el.append(this.mapView.render().el);

        this.updateLayout();

        return this;
    },

    whenAttachedToDom: function (callback) {
        if (jQuery.contains(document.documentElement, this.el)) {
            // If the element is already attached to the DOM, execute the callback now.
            callback();
        } else {
            // Otherwise, queue it up for after the view manager attaches the element to the DOM.
            this.onAttachedToDomListeners.push(callback);
        }
    },

    attachedToDom: function () {
        _.each(this.onAttachedToDomListeners, function (callback) {
            callback.apply(this);
        }, this);
        this.onAttachedToDomListeners = [];
    },

    // Set visibility and CSS classes depending on the current layout.
    updateLayout: function () {
        app.viewman.currentLayout == 'full' ? this.showFullView() : this.updateCompactView();
        this.listView.updateLayout();
        this.resize();
    },

    updateCompactView: function () {
        app.viewman.compactFindView == 'map' ? this.showCompactMapView() : this.showCompactListView();
    },

    showFullView: function () {
        this.$searchlist.addClass('twocol-layout');
        this.$searchlist.show();
        this.mapView.$el.show();
        $('#show-list-btn').hide();
        $('#show-map-btn').hide();

        var self = this;
        this.whenAttachedToDom(function () {
            if (self.collection.selected) {
                self.listView.onSelectPlayground(self.collection.selected);
                self.mapView.showPlayground(self.collection.selected);
            }
        });
    },

    showCompactListView: function () {
        this.$searchlist.removeClass('twocol-layout');
        this.$searchlist.show();
        this.mapView.$el.hide();
        $('#show-list-btn').hide();
        $('#show-map-btn').show();
        this.listView.setButtonVisibility();

        var self = this;
        this.whenAttachedToDom(function () {
            if (self.collection.selected) self.listView.onSelectPlayground(self.collection.selected);
        });
    },

    showCompactMapView: function () {
        this.$searchlist.hide();
        this.mapView.$el.show();
        this.mapView.resizeMap();
        $('#show-list-btn').show();
        $('#show-map-btn').hide();

        var self = this;
        this.whenAttachedToDom(function () {
            if (self.collection.selected) self.mapView.showPlayground(self.collection.selected);
        });
    },

    clickListBtn: function () {
        console.log('clickListBtn');
        app.viewman.compactFindView = 'list';
        console.log(app.viewman.compactFindView);
        this.updateCompactView();
    },

    clickMapBtn: function () {
        app.viewman.compactFindView = 'map';
        this.updateCompactView();
    },

    resize: function () {
        this.$searchlist.height($(window).height() - app.headerView.$el.height());
        if (this.mapView.$el.is(':visible')) {
            this.mapView.resizeMap();
        }
    },

    beforeClose: function () {
        this.searchFormView.close();
        this.listView.close();
        this.mapView.close();
    }
});

app.SearchFormView = Backbone.View.extend({
    // TODO: Extend backbone events. Emit a custom "search" event and listen to it ex. in the map view to adjust the map based on the request (ex. center on search position and set a pushpin marker, etc.)
    tagName: 'div',

    className: 'search-form',

    template: _.template($('#tpl-search-form').html()),

    render: function (eventName) {
        this.$el.html(this.template());
        return this;
    },

    // Stores request data that was collected from the form and sent to the REST API.
    requestData: {},

    events: {
        'click #search-btn': 'clickSearch',
        'click .toggle-search-options': 'toggleSearchOps'
    },

    toggleSearchOps: function (event) {
        alert('Toggle');
        event.stopPropagation();
        event.preventDefault();
    },

    clickSearch: function () {
        var self = this;

        if (this.lat && this.lng) {
            this.searchPlaygrounds({lat: lat, lng: lng});
            return;
        }

        // this.searchPlaygrounds();

        // Get the user's current location.
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                var coords = position.coords || position.coordinate || position;
                app.currentPosition = {lat: coords.latitude, lng: coords.longitude};
                self.searchPlaygrounds(app.currentPosition);
            }, self.geolocateError)
        } else {
            this.geolocateError(-1);
        }
    },

    searchNearGeocodedPosition: function (position) {
        var coords = position.coords || position.coordinate || position;
        /*
        $("#current-lat").val(coords.latitude);
        $("#current-lng").val(coords.longitude);
        */

        searchLocationsNear(coords.latitude, coords.longitude);
    },

    geolocateError: function (err) {
        var msg;
        switch(err.code) {
            case err.UNKNOWN_ERROR:        msg = "Unable to find your location"; break;
            case err.PERMISSION_DENINED:   msg = "Permission denied in finding your location"; break;
            case err.POSITION_UNAVAILABLE: msg = "Your location is currently unknown"; break;
            case err.BREAK:                msg = "Attempt to find location took too long"; break;
            default:                       msg = "Location detection not supported in browser";
        }
        alert(msg);
    }

});

app.MapView = Backbone.View.extend({
    viewName: 'map',

    id: 'map',

    map: null,
    playgroundMarkers: {},
    activeMarker: null,
    defaultMapCenter: null,
    markerIconSelected: 'http://maps.google.com/mapfiles/ms/icons/yellow-dot.png',
    markerIconSmall: 'img/red-marker-small.png',

    infoTemplate: _.template($('#tpl-playground-info-popup').html()),

    initialize: function() {
        // Center of Raleigh
        this.defaultMapCenter = new google.maps.LatLng(35.772096,-78.638614);

        this.listenTo(this.collection, 'selectPlayground', this.showPlayground);
        this.listenTo(this.collection, 'add', this.addPlaygroundMarker);
        this.listenTo(this.collection, 'reset', this.addPlaygroundMarkers);
        this.listenTo(app.viewman, 'changeLayout', this.updateLayout);

        this.$mapInfoPanel = $('<div/>', {id: 'map-info-panel'});
        this.$el.append(this.$mapInfoPanel);
        this.$mapCanvas = $('<div/>', {id: 'map-canvas'});
        this.$el.append(this.$mapCanvas);

        /*
        // Set a new center marker, if it has changed.
        this.collection.bind('change', function () {
            var requestData = app.searchFormView.requestData;
            if (requestData.lat && requestData.lng) {
                new google.maps.Marker({
                    position: new google.maps.LatLng(requestData.lat, requestData.lng),
                    map: self.map,
                    icon: 'img/blue-measle-halo.png'
                });
            }
        });
        */

    },

    render: function () {
        var options = {
            center: this.defaultMapCenter,
            zoom: 11,
            mapTypeId: google.maps.MapTypeId.HYBRID,
            // mapTypeControlOptions: {style: google.maps.MapTypeControlStyle.DROPDOWN_MENU}
            mapTypeControl: false,
            panControl: false,
            streetViewControl: false,
            zoomControlOptions: {
                style: google.maps.ZoomControlStyle.SMALL
            },
            styles: [{featureType: "poi", stylers: [ { visibility: "off" } ] }]
        };

        this.map = new google.maps.Map(this.$mapCanvas[0], options);

        this.addPlaygroundMarkers();

        return this;
    },

    addPlaygroundMarkers: function () {
        // TODO: Could use this.collection.each ?
        _.each(this.collection.models, function (playground) {
            this.addPlaygroundMarker(playground);
        }, this);
    },

    addPlaygroundMarker: function (model) {
        var position = new google.maps.LatLng(model.get('lat'), model.get('lng'));

        var marker = new google.maps.Marker({
            position: position,
            map: this.map,
            title: model.get('name'),
            icon: this.markerIconSmall
        });

        this.playgroundMarkers[model.id] = marker;

        google.maps.event.addListener(marker, 'click', function() {
            app.playgrounds.setSelected(model);
        });

    },

    recenterMap: function () {
        if (this.activeMarker) {
            this.map.setCenter(this.activeMarker.position);
        } else if (app.currentPosition) {
            this.map.setCenter(new google.maps.LatLng(app.currentPosition.lat, app.currentPosition.lng));
        } else {
            this.map.setCenter(this.defaultMapCenter);
        }
    },

    showPlayground: function (playground) {
        var marker;

        // Deactivate the previously active map marker.
        if (this.activeMarker !== null) {
            this.activeMarker.setIcon(this.markerIconSmall);
            this.activeMarker.setZIndex(1);
        }

        // Activate the map marker for the selected playground.
        marker = this.playgroundMarkers[playground.id];
        this.activeMarker = marker;
        this.map.panTo(marker.getPosition());
        // marker.setIcon('http://maps.google.com/mapfiles/ms/icons/yellow-dot.png');
        marker.setIcon(this.markerIconSelected);
        marker.setZIndex(999);

        // Show info, either as a popup (in full layout) or above the map (in compact layout).
        if (app.viewman.currentLayout == 'full') {
            if (this.infoPanelView) this.infoPanelView.close();
            if (this.infowindow) this.infowindow.close();
            this.infowindow = new google.maps.InfoWindow({
                content: this.infoTemplate(playground.toJSON())
            });
            this.infowindow.open(this.map, marker);
        }
        if (app.viewman.currentLayout == 'compact') {
            if (this.infowindow) this.infowindow.close();
            if (this.infoPanelView) this.infoPanelView.close();
            this.infoPanelView = new app.SelectedPlaygroundItemView({model: playground});
            this.$mapInfoPanel.html(this.infoPanelView.render().el);
            this.$mapInfoPanel.show();
            this.resizeMap();
        }
    },

    resizeMap: function () {
        this.$mapCanvas.height($(window).height() - app.headerView.$el.height() - this.$mapInfoPanel.height());
        google.maps.event.trigger(this.map, 'resize');

        // TODO: Do we ever want to resize without recentering?
        this.recenterMap();
    },

    updateLayout: function () {
        if (this.collection.selected) {
            this.showPlayground(this.collection.selected);
        }
    },

    beforeClose: function () {
        if (this.infoPanelView) {
            this.infoPanelView.close();
        }
    }
});

// TODO: Rename to mapInfoPanelView?
app.SelectedPlaygroundItemView = Backbone.View.extend({
    template: _.template($('#tpl-playground-info-popup').html()),

    initialize: function () {
        this.listenTo(this.model, 'change', this.render);
    },

    render: function () {
        this.$el.html(this.template(this.model.toJSON()));
        return this;
    }
});

app.ListView = Backbone.View.extend({
    viewName: 'list',

    tagName: 'ul',

    className: 'playground-list',

    id: 'playground-list',

    listItemViews: {},

    initialize: function () {
        // TODO: Should this use listenTo instead, so listeners get unbound when the view is removed?
        this.collection.bind('reset', this.addPlaygrounds, this);
        this.collection.bind('add', this.onAdd, this);

        this.collection.bind('selectPlayground', this.onSelectPlayground, this);
    },

    onAdd: function (playground) {
        this.addPlayground(playground);
        this.updateLayout();
    },

    addPlayground: function (playground) {
        var listItemView = new app.PlaygroundListItemView({model: playground});
        this.listItemViews[playground.id] = listItemView;
        this.$el.append(listItemView.render().el);
    },

    // TODO: Should this just be the render method?
    addPlaygrounds: function () {
        // _.each(this.collection.models, function (playground) {
        self = this;
        this.collection.each(function (playground) {
           self.addPlayground(playground);
        });

        this.updateLayout();
    },

    render: function (eventName) {
        this.addPlaygrounds();
        return this;
    },

    onSelectPlayground: function (playground) {
        // Highlight the selected playground in the list.
        $('.playground-list .highlight').removeClass('highlight');
        this.listItemViews[playground.id].$el.addClass('highlight');
        // $('#playground-list-item-' + playground.id).addClass('highlight');

        this.scrollIntoView(playground);
    },

    scrollTo: function (model) {
        app.findView.$searchlist.scrollTo(this.listItemViews[model.id].$el, 250)

        // $("#sidebar").scrollTo($('#playground-list-item-'+model.id), 250);
    },

    // Scroll the item to the top only if it is not already visible in the viewport.
    scrollIntoView: function (model) {
        if (!this.isScrolledIntoView(model)) {
            this.scrollTo(model);
        }
    },

    isScrolledIntoView: function (model) {
        // Check if the item is already in view.
        $item = this.listItemViews[model.id].$el;
        $viewport = app.findView.$searchlist;

        viewTop = $viewport.offset().top;
        viewBottom = viewTop + $viewport.height();

        itemTop = $item.offset().top;
        itemBottom = itemTop + $item.height();

        return ((itemBottom >= viewTop) && (itemTop <= viewBottom)
            && (itemBottom <= viewBottom) &&  (itemTop >= viewTop) );
    },

    updateLayout: function () {
        this.setButtonVisibility();
    },

    setButtonVisibility: function () {
        // Set visibility of buttons depending on the current layout.
        if (app.viewman.currentLayout == 'full') {
            this.$el.find('.view-on-map-btn').hide();
            this.$el.find('.view-details-btn').show();
        } else if (app.viewman.compactFindView == 'list') {
            this.$el.find('.view-on-map-btn').show();
            this.$el.find('.view-details-btn').hide();
        }
    }
});

app.PlaygroundListItemView = Backbone.View.extend({
    tagName: "li",

    template: _.template($('#tpl-playground-list-item').html()),

    attributes: function () {
        return {
            'id': 'playground-list-item-' + this.model.id
        }
    },

    initialize: function () {
        this.listenTo(this.model, 'change', this.render);
        this.listenTo(this.model, 'destroy', this.close);
    },

    events: {
        "click .view-on-map-btn": "viewOnMap",
        "click": "select"
    },

    render: function (eventName) {
        this.$el.html(this.template(this.model.toJSON()));
        return this;
    },

    close: function () {
        this.$el.unbind();
        this.$el.remove();
    },

    viewOnMap: function (event) {
        app.playgrounds.setSelected(this.model);
        app.viewman.setCompactFindView('map');

        event.stopPropagation();
    },

    select: function () {
        app.playgrounds.setSelected(this.model);

        if (app.viewman.currentLayout == 'compact') {
            app.router.navigate('playground/'+this.model.id, {trigger: true});
        }
    }
});

app.PlaygroundDetailsView = Backbone.View.extend({
    viewName: 'details',

    template: _.template($('#tpl-playground-details').html()),

    initialize: function () {
        this.listenTo(this.model, 'change', this.render);
        this.listenTo(this.model, 'destroy', this.close);
        this.listenTo(app.headerView, 'clickBackBtn', this.clickBackBtn);
    },

    render: function (eventName) {
        this.$el.html(this.template(this.model.toJSON()));
        _.each(this.model.get('images'), function (image) {
            this.$('.playground-images').append(new app.PlaygroundImageView({model: image}).render().el);
        }, this);

        $('#back-btn').show();

        return this;
    },

    clickBackBtn: function () {
        app.router.navigate('', {trigger: true});
    }
});

app.PlaygroundImageView = Backbone.View.extend({
    tagName: 'li',

    template: _.template($('#tpl-playground-image').html()),

    render: function (eventName) {
        this.$el.html(this.template(this.model));
        return this;
    }
});

// Router / controllers --------------------

app.Router = Backbone.Router.extend({
    routes: {
        "": "find",
        "playground/:id": "details",
        "playground/:id/edit": "edit"
    },

    initialize: function () {
        var self = this;

        // Determine the layout based on client screen size.
        app.viewman.determineLayout();

        // Initialize the models
        app.playgrounds = new app.Playgrounds();

        // Render the header
        app.headerView = new app.HeaderView({el: $('#header')});
        app.headerView.render();

        /*
        // Initialize the data.
        // Note: Don't do an asynchronous fetch() here.
        // Async calls should be done in the individual route,
        // so code that depends on the data can be executed in the "success" callback.
        if (app.initialPlaygroundData) {
            app.playgrounds.reset(app.initialPlaygroundData);
        }
        */
        if (navigator.geolocation) {
            navigator.geolocation.getCurrentPosition(function (position) {
                var coords = position.coords || position.coordinate || position;
                app.playgrounds.fetch({data: { lat: coords.latitude, lng: coords.longitude}});
            }, function (err) { self.showGeolocateError(err); app.playgrounds.fetch(); });
        } else {
            app.playgrounds.fetch();
        }
    },

    find: function () {
        app.findView = new app.FindView({collection: app.playgrounds});

        app.viewman.showView(app.findView, '#content');

        app.findView.mapView.resizeMap(); // Need to kick Google map after the element is actually inserted into the DOM.
    },

    details: function (id) {
        this.getPlayground(id, function (playground) {
            app.viewman.showView(new app.PlaygroundDetailsView({model: playground}), '#content');
        });
    },

    edit: function () {
        console.log('edit');
    },

    getPlayground: function (id, callback) {
        var model = app.playgrounds.get(id);
        if (model) {
            callback(model);
        } else {
            model = new app.Playground({id: id});
            model.fetch({success: function(model) {
                callback(model);
            }});
        }
    },

    showGeolocateError: function (err) {
        var msg;
        switch(err.code) {
            case err.UNKNOWN_ERROR:        msg = "Unable to find your location"; break;
            case err.PERMISSION_DENINED:   msg = "Permission denied in finding your location"; break;
            case err.POSITION_UNAVAILABLE: msg = "Your location is currently unknown"; break;
            case err.BREAK:                msg = "Attempt to find location took too long"; break;
            default:                       msg = "Location detection not supported in browser";
        }
        alert(msg);
    }
});

// Initialization -------------------------

app.viewman = new app.ViewManager();
$(window).resize(function () { app.viewman.onResize(); });

app.router = new app.Router();
Backbone.history.start();


