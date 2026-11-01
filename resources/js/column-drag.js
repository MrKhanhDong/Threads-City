export function setupColumnDrag({ workspace, pageWindow, canDrag, getOrder, setOrder }) {
    const columns = [...workspace.querySelectorAll('[data-column-slot]')];
    const headers = [...workspace.querySelectorAll('[data-column-drag]')];
    const animations = new Map();
    const reducedMotion = pageWindow.matchMedia('(prefers-reduced-motion: reduce)');
    const interactive = 'a,button,input,textarea,select,summary,[role="button"],[contenteditable="true"]';
    let gesture = null;

    const layoutRect = element => {
        const parent = workspace.getBoundingClientRect();
        return { left: parent.left + element.offsetLeft, top: parent.top + element.offsetTop, width: element.offsetWidth, height: element.offsetHeight };
    };
    const animateFrom = (element, before) => {
        animations.get(element)?.cancel();
        if (reducedMotion.matches || !element.animate) return;
        const after = layoutRect(element);
        const x = before.left - after.left, y = before.top - after.top;
        if (Math.abs(x) + Math.abs(y) < 1) return;
        animations.set(element, element.animate([
            { transform: `translate3d(${x}px, ${y}px, 0)` }, { transform: 'translate3d(0, 0, 0)' },
        ], { duration: 190, easing: 'cubic-bezier(.2,.8,.2,1)' }));
    };
    const changeOrder = (next, floating = null) => {
        const before = new Map(columns.filter(element => !element.hidden && element !== floating).map(element => [element, element.getBoundingClientRect()]));
        setOrder(next);
        before.forEach((rect, element) => animateFrom(element, rect));
    };
    const moveTo = (source, target) => {
        const current = getOrder();
        const index = current.indexOf(target);
        if (index < 0 || source === target || !current.includes(source)) return null;
        const next = current.filter(id => id !== source);
        next.splice(index, 0, source);
        return next;
    };
    const clearGesture = () => {
        const previous = gesture;
        gesture = null;
        if (previous?.frame !== null && previous?.frame !== undefined) pageWindow.cancelAnimationFrame(previous.frame);
        workspace.classList.remove('is-reordering');
        columns.forEach(element => { element.classList.remove('is-dragging'); element.style.transform = ''; });
        if (previous?.header.hasPointerCapture?.(previous.pointerId)) previous.header.releasePointerCapture(previous.pointerId);
    };
    const render = () => {
        if (!gesture?.started) return;
        gesture.frame = null;
        const dx = gesture.x - gesture.startX, dy = gesture.y - gesture.startY;
        const center = gesture.startRect.left + gesture.startRect.width / 2 + dx;
        const order = getOrder();
        const currentIndex = order.indexOf(gesture.id);
        let target = null;
        columns.filter(element => !element.hidden && element !== gesture.element).forEach(element => {
            const rect = layoutRect(element), index = order.indexOf(element.dataset.columnSlot);
            if ((index < currentIndex && center < rect.left + rect.width / 2) || (index > currentIndex && center > rect.left + rect.width / 2)) target = element.dataset.columnSlot;
        });
        if (target) changeOrder(moveTo(gesture.id, target), gesture.element);
        const natural = layoutRect(gesture.element);
        gesture.element.style.transform = `translate3d(${gesture.startRect.left + dx - natural.left}px, ${gesture.startRect.top + dy - natural.top}px, 0)`;
    };
    const finish = cancelled => {
        if (!gesture) return;
        if (gesture.started) {
            render();
            const source = gesture.element;
            const before = source.getBoundingClientRect();
            if (cancelled) changeOrder(gesture.initialOrder, source);
            clearGesture();
            animateFrom(source, before);
        } else {
            clearGesture();
        }
    };

    headers.forEach(header => {
        header.addEventListener('pointerdown', event => {
            if (!canDrag() || event.button !== 0 || event.target.closest(interactive)) return;
            const element = columns.find(column => column.dataset.columnSlot === header.dataset.columnDrag);
            if (!element || element.hidden) return;
            clearGesture();
            animations.forEach(animation => animation.cancel());
            gesture = { id: header.dataset.columnDrag, element, header, pointerId: event.pointerId,
                startX: event.clientX, startY: event.clientY, x: event.clientX, y: event.clientY,
                startRect: layoutRect(element), initialOrder: [...getOrder()], started: false, frame: null };
            header.setPointerCapture(event.pointerId);
        });
        header.addEventListener('pointermove', event => {
            if (!gesture || event.pointerId !== gesture.pointerId) return;
            gesture.x = event.clientX; gesture.y = event.clientY;
            if (!gesture.started && Math.hypot(gesture.x - gesture.startX, gesture.y - gesture.startY) < 6) return;
            event.preventDefault();
            gesture.started = true;
            workspace.classList.add('is-reordering');
            gesture.element.classList.add('is-dragging');
            if (gesture.frame === null) gesture.frame = pageWindow.requestAnimationFrame(render);
        });
        header.addEventListener('pointerup', event => { if (gesture?.pointerId === event.pointerId) finish(false); });
        header.addEventListener('pointercancel', () => finish(true));
        header.addEventListener('lostpointercapture', () => finish(true));
        header.addEventListener('keydown', event => {
            if (event.key === 'Escape' && gesture) { event.preventDefault(); finish(true); return; }
            if (!canDrag() || event.target.closest(interactive) || !['ArrowLeft', 'ArrowRight'].includes(event.key)) return;
            event.preventDefault();
            const order = getOrder(), index = order.indexOf(header.dataset.columnDrag);
            const next = moveTo(header.dataset.columnDrag, order[index + (event.key === 'ArrowLeft' ? -1 : 1)]);
            if (next) changeOrder(next);
        });
    });
    return clearGesture;
}
