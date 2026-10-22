import './bootstrap';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import Chart from 'chart.js/auto';
import { initTimeline } from './modules/timeline';

window.Alpine = Alpine;
window.Sortable = Sortable?.default || Sortable;
window.Chart = Chart?.default || Chart;
window.initTimeline = initTimeline;

Alpine.start();

