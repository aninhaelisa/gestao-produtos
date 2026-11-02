document.getElementById('btn-add-cesta')?.addEventListener('click', function (e) {
    const marcados = document.querySelectorAll('.checkbox-produto:checked');
    if (marcados.length === 0) {
        e.preventDefault();
        alert('Selecione pelo menos 1 produto antes de adicionar à cesta.');
    }
});