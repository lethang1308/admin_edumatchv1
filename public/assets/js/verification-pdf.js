import { getDocument, GlobalWorkerOptions } from '../pdfjs/pdf.mjs';

GlobalWorkerOptions.workerSrc = '/assets/pdfjs/pdf.worker.mjs';
const viewers = new WeakMap();

async function openViewer(element) {
    if (viewers.has(element)) return;
    const status = element.querySelector('[data-pdf-status]');
    const canvas = element.querySelector('canvas');
    const previous = element.querySelector('[data-pdf-previous]');
    const next = element.querySelector('[data-pdf-next]');
    const zoom = element.querySelector('[data-pdf-zoom]');
    const state = { active: true, page: 1, render: null, pdf: null, generation: 0 };
    viewers.set(element, state);
    const task = getDocument({ url: element.dataset.src, isEvalSupported: false, useSystemFonts: true });
    state.task = task;
    const render = async () => {
        const version = ++state.generation;
        state.render?.cancel();
        try {
            const page = await state.pdf.getPage(state.page);
            if (!state.active || version !== state.generation) return;
            const base = page.getViewport({ scale: 1 });
            const scale = Math.min(2, (element.clientWidth || 800) / base.width) * Number(zoom.value);
            const viewport = page.getViewport({ scale });
            canvas.width = viewport.width;
            canvas.height = viewport.height;
            canvas.style.maxWidth = Number(zoom.value) === 1 ? '100%' : '';
            state.render = page.render({ canvas, canvasContext: canvas.getContext('2d'), viewport });
            await state.render.promise;
            if (!state.active || version !== state.generation) return;
            status.textContent = `Trang ${state.page} / ${state.pdf.numPages}`;
            previous.disabled = state.page <= 1;
            next.disabled = state.page >= state.pdf.numPages;
        } catch (error) {
            if (state.active && error.name !== 'RenderingCancelledException') status.textContent = 'Không thể hiển thị trang PDF.';
        }
    };
    try {
        state.pdf = await task.promise;
        if (!state.active) return;
        previous.onclick = () => { if (state.page > 1) { state.page--; render(); } };
        next.onclick = () => { if (state.page < state.pdf.numPages) { state.page++; render(); } };
        zoom.onchange = render;
        await render();
    } catch {
        if (state.active) status.textContent = 'Không thể đọc PDF. Tệp có thể bị hỏng hoặc được bảo vệ bằng mật khẩu.';
    }
}

document.addEventListener('shown.bs.modal', (event) => {
    for (const element of event.target.querySelectorAll('[data-verification-pdf]')) openViewer(element);
});

// The module can finish loading after a user has already opened a modal.
for (const element of document.querySelectorAll('.modal.show [data-verification-pdf]')) openViewer(element);
document.addEventListener('hidden.bs.modal', (event) => {
    for (const element of event.target.querySelectorAll('[data-verification-pdf]')) {
        const state = viewers.get(element);
        if (!state) continue;
        state.active = false;
        state.render?.cancel();
        state.task.destroy();
        viewers.delete(element);
        element.querySelector('[data-pdf-status]').textContent = 'Đang tải PDF...';
    }
});
