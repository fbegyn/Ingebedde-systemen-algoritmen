import React, { Component, createRef } from 'react';
import { Map, TileLayer, Marker, Popup, Polyline } from 'react-leaflet';
import L from 'leaflet';

import './App.css';

var myIcon = L.icon({
  iconUrl: 'https://unpkg.com/leaflet@1.3.4/dist/images/marker-icon.png',
  iconSize: [25, 41],
  iconAnchor: [12.5, 41],
  popupAnchor: [0, -30]
})

class App extends Component {
  state = {
    location: {
      lat: 51.046,
      lng: 3.73,
    },
    center: {
      lat: 51.046,
      lng: 3.7300,
    },
    latlng: {
      lat: 51.046,
      lng: 3.7300,
    },
    zoom: 4,
    draggable: true,
    haveUserLocation: false,
    dropdownOpen: false,
    toggle: true
  }
  refmarker = createRef()

  componentDidMount() {
    navigator.geolocation.getCurrentPosition((position) => {
      this.setState({
        location: {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        },
        center: {
          lat: position.coords.latitude,
          lng: position.coords.longitude
        },
        draggable: true,
        haveUserLocation: true,
        zoom: 13,
      });
    }, () => {
      console.log("No GPS location found.")
      fetch('https://ipapi.co/json')
        .then(res => res.json())
        .then(location =>{
          console.log(location)
        })
    });
  }

  getFoot = (e) => {
    fetch("http://localhost:3001/php/routing.php?"
      +"from_lat=" + this.state.location.lat + "&"
      +"from_lon=" + this.state.location.lng + "&"
      +"to_lat=" + e.latlng.lat + "&"
      +"to_lon=" + e.latlng.lng + "&"
      +"transport=foot")
      .then(resp => resp.json())
      .then(json => {
        this.setState({
          route: {
            foot: {
              path: json.path,
              nodes: json.nodes,
              dist: json.distance
            }
          }
        });
        console.log(json);
      });
  }
  getBike = (e) => {
    fetch("http://localhost:3001/php/routing.php?"
      +"from_lat=" + this.state.location.lat + "&"
      +"from_lon=" + this.state.location.lng + "&"
      +"to_lat=" + e.latlng.lat + "&"
      +"to_lon=" + e.latlng.lng + "&"
      +"transport=bicycle")
      .then(resp => resp.json())
      .then(json => {
        this.setState({
          route: {
            bike: {
              path: json.path,
              nodes: json.nodes,
              dist: json.distance
            }
          }
        });
        console.log(json);
      });
  }
  getDrive = (e) => {
    fetch("http://localhost:3001/php/routing.php?"
      +"from_lat=" + this.state.location.lat + "&"
      +"from_lon=" + this.state.location.lng + "&"
      +"to_lat=" + e.latlng.lat + "&"
      +"to_lon=" + e.latlng.lng + "&"
      +"transport=car")
      .then(resp => resp.json())
      .then(json => {
        this.setState({
          route: {
            car: {
              path: json.path,
              nodes: json.nodes,
              dist: json.distance
            }
          }
        });
        console.log(json);
      });
  }

  handleClick = (e) => {
    console.log(this.state);
    this.getFoot(e);
   // this.getBike(e);
   // this.getDrive(e);
    this.setState({
      latlng: {
        lat: e.latlng.lat,
        lon: e.latlng.lng
      }
    });
  }

  updatePosition = () => {
    const marker = this.refmarker.current
    if (marker != null) {
      this.setState({
        location: marker.leafletElement.getLatLng(),
      })
    }
  }

  render() {
    const position = [this.state.center.lat, this.state.center.lng]
    const markerPosition = [this.state.location.lat, this.state.location.lng]
    return (
      <div className="map">
        <Map
        center={position}
        zoom={this.state.zoom}
        className="map"
        onClick={this.handleClick}>
        <TileLayer
          attribution='&amp;copy <a href="http://osm.org/copyright">OpenStreetMap</a> contributors'
          //url="https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png"
          url='https://{s}.basemaps.cartocdn.com/dark_all/{z}/{x}/{y}{r}.png'
        />
        {
          this.state.route ?
          <div>
          this.state.route.foot ?
          <Polyline color="lime" positions={this.state.route.foot.nodes ? this.state.route.foot.nodes : null} /> : ''
          </div>
          : ''
        }
        {
          this.state.latlng ?
          <Marker
            icon={myIcon}
            position={this.state.latlng}>
            <Popup minWidth={90}>
              Destination point
            </Popup>
          </Marker> : ''
        }
        {
          this.state.haveUserLocation ?
          <Marker
            draggable={this.state.draggable}
            onDragend={this.updatePosition}
            icon={myIcon}
            position={markerPosition}
            // $FlowFixMe: no idea why it's complaining about this
            ref={this.refmarker}>
            <Popup minWidth={90}>
              Start location
            </Popup>
          </Marker> : ''
        }
        </Map>
      </div>
    );
  }
}
export default App;
