let formDataObj = {};

function nextStep(step) {
    const currentCard = document.querySelector('.form-card.active');
    
    // Validation selon l'étape actuelle
    const currentStep = parseInt(currentCard.dataset.step);
    
    if (currentStep === 1) {
        const civilite = document.querySelector('input[name="civilite"]:checked');
        if (!civilite) {
            alert('Veuillez sélectionner une civilité');
            return;
        }
        formDataObj.civilite = civilite.value;
    }
    
    if (currentStep === 2) {
        const nom = document.getElementById('nom').value.trim();
        const prenom = document.getElementById('prenom').value.trim();
        if (!nom || !prenom) {
            alert('Veuillez remplir tous les champs');
            return;
        }
        formDataObj.nom = nom.toUpperCase();
        formDataObj.prenom = prenom;
    }
    
    if (currentStep === 3) {
        const email = document.getElementById('email').value.trim();
        if (!email || !validateEmail(email)) {
            alert('Veuillez entrer une adresse email valide');
            return;
        }
        formDataObj.email = email;
    }
    
    if (currentStep === 4) {
        const objet = document.getElementById('objet').value.trim();
        if (!objet) {
            alert('Veuillez remplir le champ objet');
            return;
        }
        formDataObj.objet = objet;
    }
    
    // Passer à l'étape suivante
    currentCard.classList.remove('active');
    const nextCard = document.querySelector(`[data-step="${step}"]`);
    nextCard.classList.add('active');
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function prevStep(step) {
    const currentCard = document.querySelector('.form-card.active');
    currentCard.classList.remove('active');
    
    const prevCard = document.querySelector(`[data-step="${step}"]`);
    prevCard.classList.add('active');
    
    // Restaurer les valeurs précédemment saisies
    if (step === 2 && formDataObj.nom) {
        document.getElementById('nom').value = formDataObj.nom;
        document.getElementById('prenom').value = formDataObj.prenom;
    }
    if (step === 3 && formDataObj.email) {
        document.getElementById('email').value = formDataObj.email;
    }
    if (step === 4 && formDataObj.objet) {
        document.getElementById('objet').value = formDataObj.objet;
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function submitForm() {
    const electrique = document.querySelector('input[name="electrique"]:checked');
    if (!electrique) {
        alert('Veuillez sélectionner une option');
        return;
    }
    formDataObj.est_electrique = electrique.value;
    
    // Soumettre via AJAX
    jQuery.ajax({
        url: formData.ajax_url,
        type: 'POST',
        data: {
            action: 'save_visitor',
            nonce: formData.nonce,
            civilite: formDataObj.civilite,
            nom: formDataObj.nom,
            prenom: formDataObj.prenom,
            email: formDataObj.email,
            objet: formDataObj.objet,
            est_electrique: formDataObj.est_electrique
        },
        success: function(response) {
            const messageDiv = document.getElementById('formMessage');
            document.querySelectorAll('.form-card').forEach(card => card.style.display = 'none');
            
            if (response.success) {
                messageDiv.className = 'form-message success';
                messageDiv.textContent = response.data.message;
                
                // Réinitialiser le formulaire après 3 secondes
                setTimeout(function() {
                    location.reload();
                }, 3000);
            } else {
                messageDiv.className = 'form-message error';
                messageDiv.textContent = response.data.message;
            }
        },
        error: function() {
            const messageDiv = document.getElementById('formMessage');
            messageDiv.className = 'form-message error';
            messageDiv.textContent = 'Erreur de connexion au serveur';
            document.querySelectorAll('.form-card').forEach(card => card.style.display = 'none');
        }
    });
}