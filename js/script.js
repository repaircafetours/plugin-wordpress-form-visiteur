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
        const telephone = document.getElementById('numero_telephone').value.trim();
        
        // Valider que au moins l'email ou le téléphone est fourni
        if (!email && !telephone) {
            alert('Veuillez fournir au moins une adresse email ou un numéro de téléphone');
            return;
        }
        
        // Valider l'email s'il est fourni
        if (email && !validateEmail(email)) {
            alert('Veuillez entrer une adresse email valide');
            return;
        }
        
        if (telephone && !validateNumTel(telephone)) {
            alert('Veuillez entrer un numéro de téléphone valide (10 chiffres)');
            return;
        }

        formDataObj.email = email;
        formDataObj.numero_telephone = telephone;
    }
    
    if (currentStep === 4) {
        const objet = document.getElementById('objet').value.trim();
        if (!objet) {
            alert('Veuillez sélectionner une catégorie');
            return;
        }
        formDataObj.objet = objet;
    }

    if (currentStep === 5) {
        const postal = document.getElementById('postal_code').value.trim();
        if (!isValidFrenchPostalCode(postal)) {
            alert('Veuillez sélectionner un code postal valide');
            return;
        }
        formDataObj.postal_code = postal;
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
        document.getElementById('numero_telephone').value = formDataObj.numero_telephone || '';
    }
    if (step === 4 && formDataObj.objet) {
        document.getElementById('objet').value = formDataObj.objet;
    }
    if (step === 5 && formDataObj.postal_code) {
        document.getElementById('postal_code').value = formDataObj.postal_code;
    }
    
    // Scroll to top
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

function validateEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function validateNumTel(telephone) {
    const re = /^[0-9]{10}$/;
    return re.test(telephone);
}

function isValidFrenchPostalCode(code) {
    const re = /^\d{5}$/;
    if (!re.test(code)) return false;
    const prefix = code.substring(0,2);
    const num = parseInt(prefix, 10);
    if ((num >= 1 && num <= 95) || prefix === '97' || prefix === '98') return true;
    return false;
}

// Suggestions & autocompletion for postal codes
function updatePostalSuggestions(val) {
    const container = document.getElementById('postalSuggestions');
    container.innerHTML = '';
    const v = val.trim();
    if (!v) return;

    const maxResults = 50;
    let results = [];

    // Helper to push formatted codes
    function pushCodes(prefix, count) {
        for (let i = 0; i < count && results.length < maxResults; i++) {
            const suffix = ('' + i).padStart(3, '0');
            results.push(prefix + suffix);
        }
    }

    // If short prefix, find department prefixes (01..95,97,98)
    if (v.length <= 2) {
        const deps = [];
        for (let i = 1; i <= 95; i++) {
            deps.push(('' + i).padStart(2, '0'));
        }
        deps.push('97', '98');
        deps.forEach(dep => {
            if (dep.startsWith(v)) {
                // show first 10 for this dep
                pushCodes(dep, 10);
            }
        });
    } else {
        // Use the first digits as prefix, pad to 5
        const prefix = v.substring(0, Math.min(5, v.length));
        const needed = 5 - prefix.length;
        if (needed === 0) {
            // exact 5 digits
            if (isValidFrenchPostalCode(prefix)) results.push(prefix);
        } else {
            // generate suffixes
            const base = prefix;
            for (let i = 0; i < 1000 && results.length < maxResults; i++) {
                const suffix = ('' + i).padStart(needed === 1 ? 1 : (needed === 2 ? 2 : 3), '0');
                let candidate = base + suffix;
                // If base length <5 pad right to 5
                if (candidate.length < 5) candidate = candidate.padEnd(5, '0');
                if (isValidFrenchPostalCode(candidate) && candidate.startsWith(v)) {
                    results.push(candidate);
                }
            }
        }
    }

    // Render suggestions
    results.slice(0, maxResults).forEach(code => {
        const div = document.createElement('div');
        div.className = 'postal-suggestion';
        div.textContent = code;
        div.addEventListener('mousedown', function(e) {
            e.preventDefault();
            document.getElementById('postal_code').value = code;
            formDataObj.postal_code = code;
            container.innerHTML = '';
        });
        container.appendChild(div);
    });
}

// Clear suggestions (used on blur)
function clearPostalSuggestions() {
    const container = document.getElementById('postalSuggestions');
    if (container) container.innerHTML = '';
}

// Bind events when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    const postalInput = document.getElementById('postal_code');
    if (postalInput) {
        postalInput.addEventListener('input', function() {
            updatePostalSuggestions(this.value);
        });
        postalInput.addEventListener('blur', function() {
            setTimeout(clearPostalSuggestions, 150);
        });
    }
});

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
            numero_telephone: formDataObj.numero_telephone,
            objet: formDataObj.objet,
            postal_code: formDataObj.postal_code,
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