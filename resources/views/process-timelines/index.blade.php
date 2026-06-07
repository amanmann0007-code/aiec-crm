@extends('layouts.app')

@section('page-title', 'Process Timelines')

@section('page-actions')
    <button type="submit" form="process-timeline-form" class="btn btn-primary">Save All</button>
@endsection

@section('content')
<form method="POST" action="{{ route('process-timelines.save') }}" id="process-timeline-form">
    @csrf
    <div id="timeline-hidden-fields"></div>

    <div class="row g-4">
        @foreach($visaTypes as $visaType)
            @php($steps = $stepsByVisaType[$visaType] ?? collect())
            <div class="col-xl-6">
                <div class="card shadow-sm timeline-card h-100">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <span>{{ $visaType }}</span>
                        <span class="small text-muted timeline-count">{{ $steps->count() }} tiles</span>
                    </div>
                    <div class="card-body">
                        <div class="timeline-tile-list" data-visa-type="{{ $visaType }}">
                            @foreach($steps as $step)
                                <div class="timeline-admin-tile" draggable="true" data-id="{{ $step->id }}">
                                    <button type="button" class="tile-drag-handle" aria-label="Drag tile">::</button>
                                    <input type="text" class="form-control form-control-sm tile-label-input" value="{{ $step->label }}" required>
                                    <label class="form-check form-switch tile-active-toggle mb-0" title="Active">
                                        <input class="form-check-input tile-active-input" type="checkbox" role="switch" {{ $step->is_active ? 'checked' : '' }}>
                                    </label>
                                    <button type="button" class="btn btn-sm btn-outline-danger tile-remove-btn">Remove</button>
                                </div>
                            @endforeach
                        </div>

                        <div class="timeline-empty-state text-muted text-center py-3 {{ $steps->count() ? 'd-none' : '' }}">
                            No timeline tiles configured.
                        </div>

                        <div class="input-group input-group-sm mt-3">
                            <input type="text" class="form-control new-tile-input" placeholder="New tile name">
                            <button type="button" class="btn btn-outline-primary add-tile-btn">Add Tile</button>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</form>
@endsection

@push('styles')
<style>
    .timeline-tile-list {
        display: flex;
        flex-direction: column;
        gap: .5rem;
        min-height: 44px;
    }
    .timeline-admin-tile {
        display: grid;
        grid-template-columns: 32px minmax(0, 1fr) auto auto;
        align-items: center;
        gap: .5rem;
        padding: .55rem;
        border: 1px solid #dbe3ef;
        border-radius: 8px;
        background: #fff;
        box-shadow: 0 1px 2px rgba(15, 23, 42, .06);
    }
    .timeline-admin-tile.dragging {
        opacity: .55;
        border-style: dashed;
        background: #eff6ff;
    }
    .tile-drag-handle {
        width: 32px;
        height: 32px;
        border: 0;
        border-radius: 6px;
        background: #f1f5f9;
        color: #64748b;
        font-weight: 700;
        cursor: grab;
        line-height: 1;
    }
    .tile-drag-handle:active { cursor: grabbing; }
    .tile-active-toggle { min-width: 42px; }
    .tile-active-toggle .form-check-input { margin-left: 0; cursor: pointer; }
    .tile-remove-btn { white-space: nowrap; }
    @media (max-width: 575.98px) {
        .timeline-admin-tile {
            grid-template-columns: 32px minmax(0, 1fr);
        }
        .tile-active-toggle,
        .tile-remove-btn {
            grid-column: 2;
            justify-self: start;
        }
    }
</style>
@endpush

@push('scripts')
<script>
(function () {
    const form = document.getElementById('process-timeline-form');
    const hiddenFields = document.getElementById('timeline-hidden-fields');
    let draggedTile = null;

    function refreshCard(card) {
        const count = card.querySelectorAll('.timeline-admin-tile').length;
        const countEl = card.querySelector('.timeline-count');
        const emptyEl = card.querySelector('.timeline-empty-state');

        if (countEl) {
            countEl.textContent = count + ' tile' + (count === 1 ? '' : 's');
        }
        if (emptyEl) {
            emptyEl.classList.toggle('d-none', count > 0);
        }
    }

    function createTile(label) {
        const tile = document.createElement('div');
        tile.className = 'timeline-admin-tile';
        tile.draggable = true;
        tile.innerHTML = `
            <button type="button" class="tile-drag-handle" aria-label="Drag tile">::</button>
            <input type="text" class="form-control form-control-sm tile-label-input" required>
            <label class="form-check form-switch tile-active-toggle mb-0" title="Active">
                <input class="form-check-input tile-active-input" type="checkbox" role="switch" checked>
            </label>
            <button type="button" class="btn btn-sm btn-outline-danger tile-remove-btn">Remove</button>
        `;
        tile.querySelector('.tile-label-input').value = label;
        bindTile(tile);
        return tile;
    }

    function bindTile(tile) {
        tile.addEventListener('dragstart', () => {
            draggedTile = tile;
            tile.classList.add('dragging');
        });

        tile.addEventListener('dragend', () => {
            tile.classList.remove('dragging');
            draggedTile = null;
            refreshCard(tile.closest('.timeline-card'));
        });

        tile.querySelector('.tile-remove-btn').addEventListener('click', () => {
            const card = tile.closest('.timeline-card');
            tile.remove();
            refreshCard(card);
        });
    }

    function getAfterElement(container, y) {
        const tiles = [...container.querySelectorAll('.timeline-admin-tile:not(.dragging)')];

        return tiles.reduce((closest, child) => {
            const box = child.getBoundingClientRect();
            const offset = y - box.top - box.height / 2;
            if (offset < 0 && offset > closest.offset) {
                return { offset, element: child };
            }

            return closest;
        }, { offset: Number.NEGATIVE_INFINITY }).element;
    }

    document.querySelectorAll('.timeline-admin-tile').forEach(bindTile);

    document.querySelectorAll('.timeline-tile-list').forEach(list => {
        list.addEventListener('dragover', event => {
            event.preventDefault();
            if (!draggedTile) return;

            const afterElement = getAfterElement(list, event.clientY);
            if (afterElement) {
                list.insertBefore(draggedTile, afterElement);
            } else {
                list.appendChild(draggedTile);
            }
        });
    });

    document.querySelectorAll('.add-tile-btn').forEach(button => {
        button.addEventListener('click', () => {
            const card = button.closest('.timeline-card');
            const input = card.querySelector('.new-tile-input');
            const label = input.value.trim();
            if (!label) {
                input.focus();
                return;
            }

            card.querySelector('.timeline-tile-list').appendChild(createTile(label));
            input.value = '';
            input.focus();
            refreshCard(card);
        });
    });

    document.querySelectorAll('.new-tile-input').forEach(input => {
        input.addEventListener('keydown', event => {
            if (event.key === 'Enter') {
                event.preventDefault();
                input.closest('.input-group').querySelector('.add-tile-btn').click();
            }
        });
    });

    form.addEventListener('submit', () => {
        hiddenFields.innerHTML = '';
        let index = 0;

        document.querySelectorAll('.timeline-tile-list').forEach(list => {
            const visaType = list.dataset.visaType;

            list.querySelectorAll('.timeline-admin-tile').forEach(tile => {
                const labelInput = tile.querySelector('.tile-label-input');
                const label = labelInput.value.trim();
                if (!label) return;

                const fields = {
                    id: tile.dataset.id || '',
                    visa_type: visaType,
                    label: label,
                    is_active: tile.querySelector('.tile-active-input').checked ? '1' : '0',
                };

                Object.entries(fields).forEach(([name, value]) => {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = `steps[${index}][${name}]`;
                    input.value = value;
                    hiddenFields.appendChild(input);
                });

                index++;
            });
        });
    });
})();
</script>
@endpush
