function toggleForm() {
    const loginForm = document.getElementById('loginForm');
    const registroForm = document.getElementById('registroForm');
    
    if (loginForm.classList.contains('hidden')) {
        registroForm.style.opacity = '0';
        registroForm.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            registroForm.classList.add('hidden');
            loginForm.classList.remove('hidden');
            loginForm.style.opacity = '0';
            loginForm.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                loginForm.style.opacity = '1';
                loginForm.style.transform = 'translateY(0)';
            }, 50);
        }, 200);
    } else {
        loginForm.style.opacity = '0';
        loginForm.style.transform = 'translateY(20px)';
        
        setTimeout(() => {
            loginForm.classList.add('hidden');
            registroForm.classList.remove('hidden');
            registroForm.style.opacity = '0';
            registroForm.style.transform = 'translateY(20px)';
            
            setTimeout(() => {
                registroForm.style.opacity = '1';
                registroForm.style.transform = 'translateY(0)';
            }, 50);
        }, 200);
    }
}

// Adicionar efeitos de foco nos inputs
document.addEventListener('DOMContentLoaded', function() {
    const inputs = document.querySelectorAll('input, textarea');
    
    inputs.forEach(input => {
        input.addEventListener('focus', function() {
            this.parentElement.style.transform = 'scale(1.02)';
        });
        
        input.addEventListener('blur', function() {
            this.parentElement.style.transform = 'scale(1)';
        });
    });
    
    // Inicializar estilos de transição
    const forms = document.querySelectorAll('#loginForm, #registroForm');
    forms.forEach(form => {
        form.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
    });
    
    const formGroups = document.querySelectorAll('.form-group');
    formGroups.forEach(group => {
        group.style.transition = 'transform 0.2s ease';
    });
});