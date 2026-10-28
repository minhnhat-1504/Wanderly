/**
 * Wanderly — Vietnam 3D Interactive Map (Three.js & d3-geo)
 * Renders the 34 reorganized administrative provinces of Vietnam with Hoang Sa & Truong Sa.
 * Supports orbit controls, smooth zoom, pan, hover lerp, province drawer, and 2D fallback.
 */

import * as THREE from 'three';
import * as d3 from 'd3-geo';

export class VietnamMap3D {
  constructor(options = {}) {
    this.container = typeof options.container === 'string'
      ? document.querySelector(options.container)
      : options.container;

    if (!this.container) {
      console.warn('VietnamMap3D: Container not found');
      return;
    }

    this.geojsonUrl = options.geojsonUrl || '/data/vn-34.geojson';
    this.onSelectProvince = options.onSelectProvince || (() => {});
    this.onRegionChange = options.onRegionChange || (() => {});

    // State
    this.activeRegion = 'all'; // 'all', 'north', 'central', 'south'
    this.selectedProvinceId = null;
    this.hoveredMesh = null;
    this.provincesData = [];
    this.provinceMeshes = new Map(); // id -> THREE.Group or Mesh
    this.isExpanded = false;
    this.isPaused = false;
    this.ctrlKeyHeld = false;
    this.isWheelActive = false;

    // Responsive sizing
    this.width = this.container.clientWidth || 600;
    this.height = this.container.clientHeight || 700;

    // Region Color Palette (refined teal / emerald tints, non-AI)
    this.regionColors = {
      north: 0x0f766e,   // Deep teal
      central: 0x115e59, // Dark slate teal
      south: 0x047857    // Forest emerald
    };
    this.hoverColor = 0x2dd4bf;    // Bright mint hover highlight
    this.selectedColor = 0x38bdf8; // Soft cyan selection
    this.dimColor = 0x134e4a;      // Muted background color

    // Camera targets
    this.defaultCameraPos = new THREE.Vector3(0, 5, 26);
    this.cameraTarget = new THREE.Vector3(0, 0, 0);
    this.targetCameraPos = this.defaultCameraPos.clone();
    this.targetLookAt = new THREE.Vector3(0, 0, 0);

    // Bounding region views
    this.regionFocus = {
      all: { pos: new THREE.Vector3(0, 4, 26), target: new THREE.Vector3(0, 0, 0) },
      north: { pos: new THREE.Vector3(-1.5, 9, 16), target: new THREE.Vector3(-1.5, 7.5, 0) },
      central: { pos: new THREE.Vector3(1.5, 1, 17), target: new THREE.Vector3(1, 0.5, 0) },
      south: { pos: new THREE.Vector3(-1, -7, 15), target: new THREE.Vector3(-1, -6.5, 0) }
    };

    // POI markers for the 6 curated destinations
    this.destinations = [
      { id: 'hagiang', name: 'Hà Giang', coords: [104.98, 22.82], region: 'north', color: '#f59e0b' },
      { id: 'halong', name: 'Hạ Long', coords: [107.08, 20.95], region: 'north', color: '#38bdf8' },
      { id: 'hoian', name: 'Hội An', coords: [108.33, 15.88], region: 'central', color: '#f43f5e' },
      { id: 'danang', name: 'Đà Nẵng', coords: [108.20, 16.05], region: 'central', color: '#10b981' },
      { id: 'dalat', name: 'Đà Lạt', coords: [108.45, 11.94], region: 'central', color: '#a855f7' },
      { id: 'phuquoc', name: 'Phú Quốc', coords: [103.96, 10.28], region: 'south', color: '#06b6d4' }
    ];

    // Sovereignty Island Clusters
    this.islandClusters = [
      {
        id: 'hoang-sa',
        name: 'Hoang Sa Islands',
        subtitle: 'Da Nang City',
        coords: [111.8, 16.5],
        radius: 0.4
      },
      {
        id: 'truong-sa',
        name: 'Truong Sa Islands',
        subtitle: 'Khanh Hoa Province',
        coords: [114.5, 9.8],
        radius: 0.5
      }
    ];

    this.checkReducedMotion();
    this.init();
  }

  checkReducedMotion() {
    this.reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  }

  async init() {
    // 1. Detect WebGL support
    if (!this.isWebGLAvailable()) {
      console.warn('VietnamMap3D: WebGL not available, falling back to 2D SVG map');
      this.renderFallback();
      return;
    }

    // 2. Setup Three.js scene, camera, renderer
    this.scene = new THREE.Scene();

    this.camera = new THREE.PerspectiveCamera(40, this.width / this.height, 0.1, 100);
    this.camera.position.copy(this.defaultCameraPos);

    this.renderer = new THREE.WebGLRenderer({
      antialias: true,
      alpha: true,
      powerPreference: 'high-performance'
    });
    this.renderer.setSize(this.width, this.height);
    this.renderer.setClearColor(0x000000, 0);
    this.renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    this.renderer.toneMapping = THREE.ACESFilmicToneMapping;
    this.renderer.toneMappingExposure = 1.05;

    this.canvas = this.renderer.domElement;
    this.canvas.id = 'three-vietnam-map';
    this.canvas.setAttribute('aria-label', 'Interactive 3D map of Vietnam 34 provinces with Hoang Sa and Truong Sa islands');
    this.canvas.setAttribute('tabindex', '0');
    this.canvas.className = 'w-full h-full block cursor-grab active:cursor-grabbing outline-none bg-transparent';

    this.container.innerHTML = '';
    this.container.appendChild(this.canvas);

    // 3. Lighting
    this.setupLights();

    // 4. Map Group & Raycasting
    this.mapGroup = new THREE.Group();
    this.scene.add(this.mapGroup);

    this.raycaster = new THREE.Raycaster();
    this.mouse = new THREE.Vector2(-999, -999);

    // 5. HTML Overlay & Tooltip
    this.createTooltipOverlay();

    // 6. Bind User Controls
    this.bindEvents();

    // 7. Load GeoJSON & build meshes
    await this.loadMapData();

    // 8. Add POI pins and Island badges
    this.addIslandSovereignty();
    this.addDestinationPins();

    // 9. Intersection Observer to pause when off-screen
    this.setupViewportObserver();

    // 10. Start Animation Loop
    this.animate();
  }

  isWebGLAvailable() {
    try {
      const canvas = document.createElement('canvas');
      return !!(window.WebGLRenderingContext && (canvas.getContext('webgl') || canvas.getContext('experimental-webgl')));
    } catch {
      return false;
    }
  }

  renderFallback() {
    this.container.innerHTML = `
      <div class="relative w-full h-[600px] flex items-center justify-center bg-teal-950/70 rounded-2xl border border-teal-800/60 p-6 overflow-hidden">
        <img src="/data/vn-34-fallback.svg" alt="Bản đồ Việt Nam 34 tỉnh thành" class="w-full h-full object-contain filter drop-shadow-lg" />
        <div class="absolute bottom-4 left-4 bg-gray-900/90 text-teal-200 text-xs px-3 py-1.5 rounded-lg border border-teal-700/50 backdrop-blur-md">
          Chế độ bản đồ 2D (Tối ưu hóa thiết bị)
        </div>
      </div>
    `;
  }

  setupLights() {
    // Ambient soft fill
    const hemiLight = new THREE.HemisphereLight(0xe0f2fe, 0x042f2e, 0.85);
    this.scene.add(hemiLight);

    // Primary directional light (clean angle)
    const dirLight1 = new THREE.DirectionalLight(0xffffff, 1.2);
    dirLight1.position.set(12, 18, 20);
    this.scene.add(dirLight1);

    // Subtle rim backlight
    const dirLight2 = new THREE.DirectionalLight(0x2dd4bf, 0.4);
    dirLight2.position.set(-15, -10, 10);
    this.scene.add(dirLight2);
  }

  createTooltipOverlay() {
    this.tooltipEl = document.createElement('div');
    this.tooltipEl.className = 'pointer-events-none absolute z-20 px-3 py-1.5 rounded-lg bg-gray-950/90 text-white text-xs font-sans shadow-xl border border-teal-500/40 opacity-0 transition-opacity duration-150 transform -translate-x-1/2 -translate-y-full mb-2 whitespace-nowrap backdrop-blur-md';
    this.container.classList.add('relative');
    this.container.appendChild(this.tooltipEl);
  }

  async loadMapData() {
    try {
      const res = await fetch(this.geojsonUrl);
      if (!res.ok) throw new Error(`HTTP error ${res.status}`);
      const geojson = await res.json();
      this.provincesData = geojson.features || [];

      // D3 GeoMercator projection tuned for Vietnam aspect ratio in Three.js world space
      // Vietnam spans from ~8.5N to ~23.5N, 102E to 110E (+ islands out to 115E)
      const worldWidth = 14;
      const worldHeight = 22;

      this.projection = d3.geoMercator()
        .fitSize([worldWidth, worldHeight], geojson);

      for (const feature of this.provincesData) {
        this.createProvinceMesh(feature, worldWidth, worldHeight);
      }

      // Initial smooth entrance
      if (!this.reducedMotion) {
        this.camera.position.set(0, 15, 36);
        this.targetCameraPos.copy(this.defaultCameraPos);
      }
    } catch (err) {
      console.error('VietnamMap3D: Failed to load GeoJSON:', err);
      this.renderFallback();
    }
  }

  createProvinceMesh(feature, worldWidth, worldHeight) {
    const props = feature.properties;
    const region = props.region || 'central';
    const baseColor = this.regionColors[region] || this.regionColors.central;

    const group = new THREE.Group();
    group.userData = {
      id: props.id,
      name: props.name,
      region: props.region,
      regionName: props.regionName,
      note: props.note || '',
      baseColor: baseColor,
      isProvince: true,
      center: props.center
    };

    const material = new THREE.MeshStandardMaterial({
      color: baseColor,
      roughness: 0.45,
      metalness: 0.1,
      flatShading: false
    });

    const edgeMaterial = new THREE.LineBasicMaterial({
      color: 0x5eead4,
      transparent: true,
      opacity: 0.25,
      linewidth: 1
    });

    const coordinates = feature.geometry.type === 'MultiPolygon'
      ? feature.geometry.coordinates
      : [feature.geometry.coordinates];

    for (const polygon of coordinates) {
      if (!polygon || polygon.length === 0) continue;

      const shape = new THREE.Shape();
      const outerRing = polygon[0];

      // Convert outer ring coordinates using projection
      for (let i = 0; i < outerRing.length; i++) {
        const [x, y] = this.projection(outerRing[i]);
        // Flip Y and center in 3D world space
        const worldX = x - worldWidth / 2;
        const worldY = -(y - worldHeight / 2);

        if (i === 0) {
          shape.moveTo(worldX, worldY);
        } else {
          shape.lineTo(worldX, worldY);
        }
      }

      // Inner rings (holes)
      for (let h = 1; h < polygon.length; h++) {
        const holeRing = polygon[h];
        const holePath = new THREE.Path();
        for (let i = 0; i < holeRing.length; i++) {
          const [hx, hy] = this.projection(holeRing[i]);
          const hwX = hx - worldWidth / 2;
          const hwY = -(hy - worldHeight / 2);
          if (i === 0) {
            holePath.moveTo(hwX, hwY);
          } else {
            holePath.lineTo(hwX, hwY);
          }
        }
        shape.holes.push(holePath);
      }

      // Extrude geometry for clean 3D relief
      const extrudeSettings = {
        depth: 0.4,
        bevelEnabled: true,
        bevelSegments: 1,
        steps: 1,
        bevelSize: 0.04,
        bevelThickness: 0.04
      };

      const geometry = new THREE.ExtrudeGeometry(shape, extrudeSettings);
      const mesh = new THREE.Mesh(geometry, material.clone());
      mesh.userData = group.userData;

      // Add edge lines
      const edges = new THREE.EdgesGeometry(geometry, 25);
      const line = new THREE.LineSegments(edges, edgeMaterial);
      mesh.add(line);

      group.add(mesh);
    }

    this.mapGroup.add(group);
    this.provinceMeshes.set(props.id, group);
  }

  addIslandSovereignty() {
    this.islandBadges = [];

    for (const island of this.islandClusters) {
      const [projX, projY] = this.projection(island.coords);
      const worldX = projX - 7;
      const worldY = -(projY - 11);

      // 3D Anchor cylinder ring
      const geo = new THREE.CylinderGeometry(0.35, 0.35, 0.15, 16);
      const mat = new THREE.MeshStandardMaterial({
        color: 0x38bdf8,
        emissive: 0x0284c7,
        emissiveIntensity: 0.5,
        roughness: 0.3
      });
      const mesh = new THREE.Mesh(geo, mat);
      mesh.rotation.x = Math.PI / 2;
      mesh.position.set(worldX, worldY, 0.3);
      this.mapGroup.add(mesh);

      // Edge glow ring
      const ringGeo = new THREE.RingGeometry(0.4, 0.55, 24);
      const ringMat = new THREE.MeshBasicMaterial({
        color: 0x38bdf8,
        side: THREE.DoubleSide,
        transparent: true,
        opacity: 0.7
      });
      const ringMesh = new THREE.Mesh(ringGeo, ringMat);
      ringMesh.position.set(worldX, worldY, 0.4);
      this.mapGroup.add(ringMesh);

      this.islandBadges.push({
        id: island.id,
        name: island.name,
        subtitle: island.subtitle,
        worldPos: new THREE.Vector3(worldX, worldY, 0.5)
      });
    }

    this.renderIslandHtmlBadges();
  }

  renderIslandHtmlBadges() {
    this.islandBadgesContainer = document.createElement('div');
    this.islandBadgesContainer.className = 'pointer-events-none absolute inset-0 overflow-hidden z-10';
    this.container.appendChild(this.islandBadgesContainer);

    this.islandDomElements = this.islandBadges.map(item => {
      const el = document.createElement('div');
      el.className = 'absolute transform -translate-x-1/2 -translate-y-1/2 flex items-center gap-1.5 px-2.5 py-1 rounded-md bg-teal-950/85 border border-sky-400/50 shadow-md backdrop-blur-sm pointer-events-auto transition-transform hover:scale-105';
      el.innerHTML = `
        <span class="w-2 h-2 rounded-full bg-sky-400 animate-pulse"></span>
        <div class="leading-none text-left">
          <div class="text-[11px] font-bold text-sky-200 tracking-wide">${item.name}</div>
          <div class="text-[9px] text-teal-300 font-medium">${item.subtitle}</div>
        </div>
      `;
      this.islandBadgesContainer.appendChild(el);
      return { el, worldPos: item.worldPos };
    });
  }

  addDestinationPins() {
    this.pinElements = [];
    this.pinsContainer = document.createElement('div');
    this.pinsContainer.className = 'pointer-events-none absolute inset-0 overflow-hidden z-10';
    this.container.appendChild(this.pinsContainer);

    for (const dest of this.destinations) {
      const [projX, projY] = this.projection(dest.coords);
      const worldX = projX - 7;
      const worldY = -(projY - 11);

      // 3D Pin Head
      const pinGeo = new THREE.SphereGeometry(0.2, 16, 16);
      const pinMat = new THREE.MeshStandardMaterial({
        color: 0xffffff,
        emissive: 0x0f766e,
        emissiveIntensity: 0.6,
        roughness: 0.2
      });
      const pinMesh = new THREE.Mesh(pinGeo, pinMat);
      pinMesh.position.set(worldX, worldY, 0.7);
      this.mapGroup.add(pinMesh);

      // DOM Label Badge for destination
      const label = document.createElement('button');
      label.type = 'button';
      label.setAttribute('aria-label', `Xem điểm đến ${dest.name}`);
      label.className = 'absolute transform -translate-x-1/2 -translate-y-full mb-1 flex items-center gap-1 px-2 py-0.5 rounded-full bg-gray-900/90 text-white text-[11px] font-semibold border border-teal-400/40 shadow-lg pointer-events-auto hover:bg-teal-700 hover:border-teal-300 transition-all cursor-pointer whitespace-nowrap';
      label.innerHTML = `
        <span class="w-1.5 h-1.5 rounded-full" style="background-color: ${dest.color}"></span>
        <span>${dest.name}</span>
      `;

      label.addEventListener('click', (e) => {
        e.stopPropagation();
        this.focusDestination(dest);
      });

      this.pinsContainer.appendChild(label);
      this.pinElements.push({
        el: label,
        worldPos: new THREE.Vector3(worldX, worldY, 0.8),
        dest
      });
    }
  }

  updateScreenBadges() {
    if (!this.camera) return;

    // Update island badges screen coordinates
    if (this.islandDomElements) {
      for (const item of this.islandDomElements) {
        const screenPos = item.worldPos.clone().project(this.camera);
        const x = (screenPos.x * 0.5 + 0.5) * this.width;
        const y = (-screenPos.y * 0.5 + 0.5) * this.height;

        if (screenPos.z < 1 && x >= -50 && x <= this.width + 50 && y >= -50 && y <= this.height + 50) {
          item.el.style.display = 'flex';
          item.el.style.left = `${x}px`;
          item.el.style.top = `${y}px`;
        } else {
          item.el.style.display = 'none';
        }
      }
    }

    // Update destination pin screen coordinates
    if (this.pinElements) {
      for (const item of this.pinElements) {
        const screenPos = item.worldPos.clone().project(this.camera);
        const x = (screenPos.x * 0.5 + 0.5) * this.width;
        const y = (-screenPos.y * 0.5 + 0.5) * this.height;

        if (screenPos.z < 1 && x >= 0 && x <= this.width && y >= 0 && y <= this.height) {
          item.el.style.display = 'flex';
          item.el.style.left = `${x}px`;
          item.el.style.top = `${y}px`;
        } else {
          item.el.style.display = 'none';
        }
      }
    }
  }

  bindEvents() {
    // Resize handling
    this.resizeObserver = new ResizeObserver(() => this.onResize());
    this.resizeObserver.observe(this.container);

    // Mouse movement for hover raycasting
    this.canvas.addEventListener('mousemove', (e) => this.onMouseMove(e));
    this.canvas.addEventListener('mouseleave', () => this.onMouseLeave());
    this.canvas.addEventListener('click', (e) => this.onClick(e));
    this.canvas.addEventListener('dblclick', (e) => this.onDoubleClick(e));

    // Mouse wheel zoom (Section H.2: only zoom when holding Ctrl or when canvas clicked/active)
    window.addEventListener('keydown', (e) => {
      if (e.key === 'Control' || e.key === 'Meta') {
        this.ctrlKeyHeld = true;
      }
      if (document.activeElement === this.canvas) {
        this.handleKeyboardShortcuts(e);
      }
    });

    window.addEventListener('keyup', (e) => {
      if (e.key === 'Control' || e.key === 'Meta') {
        this.ctrlKeyHeld = false;
      }
    });

    this.canvas.addEventListener('wheel', (e) => {
      if (this.ctrlKeyHeld || this.isWheelActive) {
        e.preventDefault();
        const delta = e.deltaY > 0 ? 1.5 : -1.5;
        this.zoom(delta);
      }
    }, { passive: false });

    // Touch events for mobile pinch-to-zoom
    let initialTouchDistance = 0;
    this.canvas.addEventListener('touchstart', (e) => {
      if (e.touches.length === 2) {
        const dx = e.touches[0].clientX - e.touches[1].clientX;
        const dy = e.touches[0].clientY - e.touches[1].clientY;
        initialTouchDistance = Math.hypot(dx, dy);
      }
    }, { passive: true });

    this.canvas.addEventListener('touchmove', (e) => {
      if (e.touches.length === 2 && initialTouchDistance > 0) {
        const dx = e.touches[0].clientX - e.touches[1].clientX;
        const dy = e.touches[0].clientY - e.touches[1].clientY;
        const currentDistance = Math.hypot(dx, dy);
        const diff = initialTouchDistance - currentDistance;
        if (Math.abs(diff) > 5) {
          this.zoom(diff * 0.05);
          initialTouchDistance = currentDistance;
        }
      }
    }, { passive: true });

    // Click outside clears active wheel mode
    document.addEventListener('click', (e) => {
      if (!this.container.contains(e.target)) {
        this.isWheelActive = false;
      }
    });
  }

  handleKeyboardShortcuts(e) {
    if (e.key === '+' || e.key === '=') {
      e.preventDefault();
      this.zoom(-2);
    } else if (e.key === '-' || e.key === '_') {
      e.preventDefault();
      this.zoom(2);
    } else if (e.key === '0') {
      e.preventDefault();
      this.resetView();
    } else if (e.key === 'ArrowUp') {
      e.preventDefault();
      this.targetLookAt.y += 0.5;
    } else if (e.key === 'ArrowDown') {
      e.preventDefault();
      this.targetLookAt.y -= 0.5;
    } else if (e.key === 'ArrowLeft') {
      e.preventDefault();
      this.targetLookAt.x -= 0.5;
    } else if (e.key === 'ArrowRight') {
      e.preventDefault();
      this.targetLookAt.x += 0.5;
    }
  }

  onResize() {
    if (!this.container || !this.renderer) return;
    this.width = this.container.clientWidth;
    this.height = this.container.clientHeight;
    this.camera.aspect = this.width / this.height;
    this.camera.updateProjectionMatrix();
    this.renderer.setSize(this.width, this.height);
  }

  onMouseMove(e) {
    const rect = this.canvas.getBoundingClientRect();
    this.mouse.x = ((e.clientX - rect.left) / this.width) * 2 - 1;
    this.mouse.y = -((e.clientY - rect.top) / this.height) * 2 + 1;

    // Tooltip position
    this.tooltipEl.style.left = `${e.clientX - rect.left}px`;
    this.tooltipEl.style.top = `${e.clientY - rect.top}px`;

    // Raycast province meshes
    this.raycaster.setFromCamera(this.mouse, this.camera);
    const intersects = this.raycaster.intersectObjects(this.mapGroup.children, true);

    const hit = intersects.find(i => i.object.userData && i.object.userData.isProvince);

    if (hit) {
      const group = hit.object.parent;
      if (this.hoveredMesh !== group) {
        this.resetHover();
        this.hoveredMesh = group;
        this.setGroupHighlight(group, true);
        this.showTooltip(group.userData);
      }
    } else {
      if (this.hoveredMesh) {
        this.resetHover();
      }
    }
  }

  onMouseLeave() {
    this.mouse.set(-999, -999);
    this.resetHover();
    this.tooltipEl.style.opacity = '0';
  }

  resetHover() {
    if (this.hoveredMesh) {
      this.setGroupHighlight(this.hoveredMesh, false);
      this.hoveredMesh = null;
    }
    this.tooltipEl.style.opacity = '0';
  }

  setGroupHighlight(group, isHovered) {
    const isSelected = group.userData.id === this.selectedProvinceId;
    const targetZ = isHovered ? 0.45 : (isSelected ? 0.35 : 0);

    group.position.z = THREE.MathUtils.lerp(group.position.z, targetZ, 0.4);

    group.traverse((child) => {
      if (child.isMesh && child.material) {
        if (isHovered) {
          child.material.color.setHex(this.hoverColor);
        } else if (isSelected) {
          child.material.color.setHex(this.selectedColor);
        } else if (this.activeRegion !== 'all' && group.userData.region !== this.activeRegion) {
          child.material.color.setHex(this.dimColor);
        } else {
          child.material.color.setHex(group.userData.baseColor);
        }
      }
    });
  }

  showTooltip(userData) {
    const noteText = userData.note ? `<div class="text-[10px] text-teal-300 font-normal mt-0.5">${userData.note}</div>` : '';
    this.tooltipEl.innerHTML = `
      <div class="font-bold text-white">${userData.name}</div>
      <div class="text-[11px] text-teal-200">${userData.regionName}</div>
      ${noteText}
    `;
    this.tooltipEl.style.opacity = '1';
  }

  onClick(e) {
    this.isWheelActive = true;
    this.canvas.focus();

    this.raycaster.setFromCamera(this.mouse, this.camera);
    const intersects = this.raycaster.intersectObjects(this.mapGroup.children, true);
    const hit = intersects.find(i => i.object.userData && i.object.userData.isProvince);

    if (hit) {
      const group = hit.object.parent;
      this.selectProvince(group.userData);
    }
  }

  onDoubleClick(e) {
    this.raycaster.setFromCamera(this.mouse, this.camera);
    const intersects = this.raycaster.intersectObjects(this.mapGroup.children, true);
    const hit = intersects.find(i => i.object.userData && i.object.userData.isProvince);

    if (hit) {
      const group = hit.object.parent;
      // Fly into province
      const center = group.userData.center || [0, 0];
      const [px, py] = this.projection(center);
      const wx = px - 7;
      const wy = -(py - 11);
      this.targetLookAt.set(wx, wy, 0);
      this.targetCameraPos.set(wx, wy, 10);
    } else {
      // Double click ocean -> reset
      this.resetView();
    }
  }

  selectProvince(userData) {
    this.selectedProvinceId = userData.id;

    // Refresh styling across all groups
    for (const [id, group] of this.provinceMeshes) {
      this.setGroupHighlight(group, false);
    }

    // Trigger external callback & custom event
    this.onSelectProvince(userData);
    window.dispatchEvent(new CustomEvent('wanderly-province-selected', { detail: userData }));
  }

  focusDestination(dest) {
    const [px, py] = this.projection(dest.coords);
    const wx = px - 7;
    const wy = -(py - 11);

    this.targetLookAt.set(wx, wy, 0);
    this.targetCameraPos.set(wx, wy, 12);

    // Also trigger province select if matches
    window.dispatchEvent(new CustomEvent('wanderly-destination-clicked', { detail: dest }));
  }

  filterRegion(region) {
    this.activeRegion = region;
    const targetConfig = this.regionFocus[region] || this.regionFocus.all;

    this.targetCameraPos.copy(targetConfig.pos);
    this.targetLookAt.copy(targetConfig.target);

    // Apply color dimming to provinces outside region
    for (const [id, group] of this.provinceMeshes) {
      const match = region === 'all' || group.userData.region === region;
      group.traverse(child => {
        if (child.isMesh && child.material) {
          child.material.color.setHex(match ? group.userData.baseColor : this.dimColor);
        }
      });
    }

    this.onRegionChange(region);
  }

  zoom(delta) {
    const minZ = 8;
    const maxZ = 34;
    this.targetCameraPos.z = THREE.MathUtils.clamp(this.targetCameraPos.z + delta, minZ, maxZ);
  }

  resetView() {
    this.activeRegion = 'all';
    this.selectedProvinceId = null;
    this.targetCameraPos.copy(this.defaultCameraPos);
    this.targetLookAt.set(0, 0, 0);

    for (const [id, group] of this.provinceMeshes) {
      this.setGroupHighlight(group, false);
    }

    window.dispatchEvent(new CustomEvent('wanderly-region-reset'));
  }

  toggleExpand() {
    this.isExpanded = !this.isExpanded;
    window.dispatchEvent(new CustomEvent('wanderly-map-expand-toggle', { detail: { isExpanded: this.isExpanded } }));
    setTimeout(() => this.onResize(), 100);
  }

  setupViewportObserver() {
    const observer = new IntersectionObserver(([entry]) => {
      this.isPaused = !entry.isIntersecting;
    }, { threshold: 0.1 });

    observer.observe(this.container);
  }

  animate() {
    this.animationFrameId = requestAnimationFrame(() => this.animate());

    if (this.isPaused) return;

    // Smooth camera damping
    const lerpFactor = this.reducedMotion ? 1.0 : 0.08;
    this.camera.position.lerp(this.targetCameraPos, lerpFactor);
    this.cameraTarget.lerp(this.targetLookAt, lerpFactor);
    this.camera.lookAt(this.cameraTarget);

    // Hover lerp for meshes
    if (this.hoveredMesh) {
      this.hoveredMesh.position.z = THREE.MathUtils.lerp(this.hoveredMesh.position.z, 0.45, 0.2);
    }

    // Update screen HTML overlay badges
    this.updateScreenBadges();

    this.renderer.render(this.scene, this.camera);
  }

  destroy() {
    if (this.animationFrameId) {
      cancelAnimationFrame(this.animationFrameId);
    }
    if (this.resizeObserver) {
      this.resizeObserver.disconnect();
    }
    if (this.renderer) {
      this.renderer.dispose();
    }
  }
}

// Global initialization helper for Alpine.js or vanilla integration
window.initVietnamMap3D = (options) => {
  return new VietnamMap3D(options);
};
