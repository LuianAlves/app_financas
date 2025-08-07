// assets/js/common/generateCrud.js
export function generateCRUD({
                                 route,
                                 inputId,
                                 modalId,
                                 formId,
                                 listSelector,
                                 renderCard,
                                 fillModal,
                                 createBtnSelector,    // ex: "#openModal" ou ".create-btn"
                                 viewBtnSelector,      // dentro do card: ex: ".view-card-btn"
                                 editBtnSelector,      // dentro do card: ex: ".edit-card-btn"
                                 deleteBtnSelector     // dentro do card: ex: ".delete-card-btn"
                             }) {
    const formEl  = document.querySelector(formId);
    const modalEl = document.querySelector(modalId);
    const listEl  = document.querySelector(listSelector);

    // 1) carrega todos os itens e limpa o container
    async function loadItems() {
        try {
            console.log('1')
            const res = await fetch(route, { headers: { 'Accept': 'application/json' } });
            if (!res.ok) throw new Error('Erro ao carregar itens');
            const payload = await res.json();
            const items = payload.data || payload;
            listEl.innerHTML = items.map(renderCard).join('');
        } catch (err) {
            alert(err.message);
        }
    }

    // 2) abre modal vazio para criação
    if (createBtnSelector) {
        document.querySelector(createBtnSelector).addEventListener('click', () => {
            formEl.reset();
            formEl.querySelector(`[name="${inputId}"]`).value = '';
            modalEl.classList.add('show');
        });
    }

    // 3) submete create ou update
    async function submitHandler(e) {
        e.preventDefault();
        const formData = new FormData(formEl);
        const id = formEl.querySelector(`[name="${inputId}"]`)?.value;
        const url    = id ? `${route}/${id}` : route;
        const method = id ? 'PUT' : 'POST';

        try {
            const res = await fetch(url, {
                method,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                },
                body: formData
            });
            if (!res.ok) throw new Error('Erro ao salvar');
            await res.json();
            formEl.reset();
            modalEl.classList.remove('show');
            await loadItems();
        } catch (err) {
            alert(err.message);
        }
    }

    formEl.addEventListener('submit', submitHandler);

    // 4) delegação de eventos dentro da lista de cards
    listEl.addEventListener('click', async e => {
        // VISUALIZAR
        const vb = e.target.closest(viewBtnSelector);
        if (vb) {
            const id = vb.dataset.id;
            const res = await fetch(`${route}/${id}`, { headers: { 'Accept': 'application/json' } });
            const payload = await res.json();
            fillModal(payload.data || payload);
            formEl.querySelectorAll('input, select, textarea').forEach(i => i.disabled = true);
            formEl.querySelector(`[name="${inputId}"]`).value = id;
            modalEl.classList.add('show');
            return;
        }

        // EDITAR
        const eb = e.target.closest(editBtnSelector);
        if (eb) {
            const id = eb.dataset.id;
            const res = await fetch(`${route}/${id}`, { headers: { 'Accept': 'application/json' } });
            const payload = await res.json();
            formEl.reset();
            fillModal(payload.data || payload);
            formEl.querySelectorAll('input, select, textarea').forEach(i => i.disabled = false);
            formEl.querySelector(`[name="${inputId}"]`).value = id;
            modalEl.classList.add('show');
            return;
        }

        // APAGAR
        const db = e.target.closest(deleteBtnSelector);
        if (db && confirm('Confirma exclusão?')) {
            const id = db.dataset.id;
            try {
                const res = await fetch(`${route}/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                    }
                });
                if (!res.ok) throw new Error('Falha ao apagar');
                await res.json();
                await loadItems();
            } catch (err) {
                alert(err.message);
            }
        }
    });

    // 5) carrega ao iniciar
    window.addEventListener('DOMContentLoaded', loadItems);
}
