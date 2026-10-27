import Sortable from 'sortablejs';

const SortableClass = Sortable?.default || Sortable || window?.Sortable;

export function initTimeline() {
    const timelineBoard = document.getElementById('timeline-board');
    if (!timelineBoard) return;

    const canEdit = timelineBoard.dataset.canEdit !== 'false';
    const tripId = timelineBoard.dataset.tripId;
    const containers = document.querySelectorAll('.timeline-slot-container');

    // Initial check for empty placeholders and recalculate totals
    containers.forEach(updateSlotEmptyState);
    recalculateDayTotals();

    if (!canEdit) {
        // Read-only / viewer mode: disable drag-and-drop reordering
        return;
    }

    if (!SortableClass) {
        console.warn('[Wanderly Timeline] Sortable library could not be loaded.');
        return;
    }

    containers.forEach((container) => {
        // Prevent duplicate initialization
        if (container._sortableInstance) {
            container._sortableInstance.destroy();
        }

        try {
            const instance = new SortableClass(container, {
            group: 'trip-timeline',
            draggable: '.timeline-activity-card',
            filter: '.slot-empty-placeholder',
            preventOnFilter: false,
            animation: 200,
            handle: '.drag-handle',
            ghostClass: 'opacity-40',
            chosenClass: 'scale-[1.02]',
            dragClass: 'shadow-2xl',
            fallbackTolerance: 3,

            onEnd: async function (evt) {
                // Ignore if dragged element is not an activity card
                if (!evt.item || !evt.item.classList.contains('timeline-activity-card')) {
                    return;
                }

                // If position hasn't changed at all, do nothing
                if (evt.from === evt.to && evt.oldIndex === evt.newIndex) {
                    return;
                }

                const itemEl = evt.item;
                const fromContainer = evt.from;
                const toContainer = evt.to;
                const oldIndex = evt.oldIndex;
                const newDay = parseInt(toContainer.dataset.day, 10);
                const newSlot = toContainer.dataset.slot;

                // Optimistically update attributes on the dragged item
                itemEl.dataset.day = newDay;
                itemEl.dataset.slot = newSlot;

                // Update empty state indicators if needed
                updateSlotEmptyState(fromContainer);
                updateSlotEmptyState(toContainer);

                // Collect reordered payload across all containers
                const reorderItems = [];
                document.querySelectorAll('.timeline-slot-container').forEach((slotEl) => {
                    const day = parseInt(slotEl.dataset.day, 10);
                    const slot = slotEl.dataset.slot;

                    slotEl.querySelectorAll('.timeline-activity-card').forEach((card, index) => {
                        const cardId = parseInt(card.dataset.id, 10);
                        if (!isNaN(cardId)) {
                            reorderItems.push({
                                id: cardId,
                                day: day,
                                time_slot: slot,
                                position: index,
                            });
                        }
                    });
                });

                // Notify UI: Saving in progress
                window.dispatchEvent(new CustomEvent('timeline-saving', {
                    detail: { saving: true }
                }));

                // Update local day total cost immediately (Optimistic calculation)
                recalculateDayTotals();

                // CSRF Token
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

                try {
                    const response = await fetch(`/ajax/trips/${tripId}/activities/reorder`, {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': csrfToken || '',
                            'X-Requested-With': 'XMLHttpRequest',
                        },
                        body: JSON.stringify({ items: reorderItems }),
                    });

                    if (!response.ok) {
                        throw new Error(`Server returned HTTP ${response.status}`);
                    }

                    const data = await response.json();

                    // Notify success and broadcast for budget updates
                    window.dispatchEvent(new CustomEvent('timeline-updated', {
                        detail: {
                            items: reorderItems,
                            serverData: data
                        }
                    }));

                } catch (error) {
                    console.error('[Timeline] Reorder failed, performing rollback:', error);

                    // ==========================================
                    // ROLLBACK MECHANISM
                    // ==========================================
                    if (fromContainer && fromContainer.children && fromContainer.children[oldIndex]) {
                        fromContainer.insertBefore(itemEl, fromContainer.children[oldIndex]);
                    } else if (fromContainer) {
                        fromContainer.appendChild(itemEl);
                    }

                    // Restore previous day & slot
                    if (fromContainer) {
                        itemEl.dataset.day = fromContainer.dataset.day;
                        itemEl.dataset.slot = fromContainer.dataset.slot;
                        updateSlotEmptyState(fromContainer);
                    }
                    if (toContainer) {
                        updateSlotEmptyState(toContainer);
                    }
                    recalculateDayTotals();

                    // Notify user via Toast
                    window.dispatchEvent(new CustomEvent('show-toast', {
                        detail: {
                            type: 'error',
                            message: 'Could not save new order. Position was restored.'
                        }
                    }));
                } finally {
                    window.dispatchEvent(new CustomEvent('timeline-saving', {
                        detail: { saving: false }
                    }));
                }
            },
        });

        container._sortableInstance = instance;
    } catch (sortErr) {
        console.error('[Wanderly Timeline] Error initializing sortable on container:', sortErr);
    }
});

    // Helper: Hide/Show empty placeholder in slot
    function updateSlotEmptyState(container) {
        const hasCards = container.querySelectorAll('.timeline-activity-card').length > 0;
        const emptyNotice = container.querySelector('.slot-empty-placeholder');
        if (emptyNotice) {
            emptyNotice.style.display = hasCards ? 'none' : 'flex';
        }
    }

    // Helper: Recalculate and display total cost for each day
    function recalculateDayTotals() {
        const dayTotals = {};

        document.querySelectorAll('.timeline-activity-card').forEach((card) => {
            const day = card.dataset.day;
            const cost = parseFloat(card.dataset.cost || 0);
            dayTotals[day] = (dayTotals[day] || 0) + cost;
        });

        document.querySelectorAll('[data-day-total-display]').forEach((display) => {
            const day = display.dataset.dayTotalDisplay;
            const total = dayTotals[day] || 0;
            display.textContent = new Intl.NumberFormat('vi-VN').format(total) + ' VND';
        });

        // Trigger budget widget recalculation
        window.dispatchEvent(new CustomEvent('budget-recalculate'));
    }

    // Initial check for empty placeholders
    containers.forEach(updateSlotEmptyState);
    recalculateDayTotals();
}

// Auto-initialize when DOM is ready
if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initTimeline);
} else {
    initTimeline();
}
