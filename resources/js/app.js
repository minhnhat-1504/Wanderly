import './bootstrap';

import Alpine from 'alpinejs';
import Sortable from 'sortablejs';
import Chart from 'chart.js/auto';
import { initTimeline } from './modules/timeline';
import { initChatDrawer } from './modules/chat';

window.Alpine = Alpine;
window.Sortable = Sortable?.default || Sortable;
window.Chart = Chart?.default || Chart;
window.initTimeline = initTimeline;
window.initChatDrawer = initChatDrawer;

Alpine.start();

