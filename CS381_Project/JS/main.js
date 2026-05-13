// Get an emoji icon for a category
function categoryIcon(cat) {
    if (cat === 'electronics') return '🔌';
    if (cat === 'bags') return '🎒';
    if (cat === 'clothing') return '👕';
    if (cat === 'keys') return '🔑';
    if (cat === 'accessories') return '⌚';
    if (cat === 'books')  return '📚';
    return '📦'; //else
}


// Show message at the bottom of the screen 
function toast(message, type) {
    var area = document.getElementById('toast-area');
    if (!area) {
        area = document.createElement('div');
        area.id = 'toast-area';
        area.className = 'notifications-area';
        document.body.appendChild(area); //add to the end of body
    }

    var box = document.createElement('div');
    box.className = 'notification ' + (type || '');
    box.textContent = message;
    area.appendChild(box);

    // remove it after 3 seconds, notif
    setTimeout(function() {
        box.style.opacity = '0';
        box.style.transition = 'opacity 0.3s';
        setTimeout(function() { box.remove(); }, 300);
    }, 3000);
}


//toggling open class
function openModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.add('open');
}

function closeModal(id) {
    var el = document.getElementById(id);
    if (el) el.classList.remove('open');
}

// close popup if user clicks the dark area outside
document.addEventListener('click', function(e) {
    if (e.target.classList.contains('popup-overlay')) {
        e.target.classList.remove('open');
    }
});


// Check input field is valid
function validateField(input) {
    var group = input.closest('.input-group');
    var errEl = group ? group.querySelector('.input-error') : null;
    var ok  = true;
    var msg = '';

    if (input.required && !input.value.trim()) {
        ok = false;
        msg = 'This feild is required.';
    } else if (input.type === 'email' && input.value) {
        //email check
        if (input.value.indexOf('@') < 0 || input.value.indexOf('.') < 0) {
            ok = false;
            msg = 'Please enter a valide email adress.';
        }
    } else if (input.minLength > 0 && input.value.length < input.minLength) {
        ok = false;
        msg = 'Minimum ' + input.minLength + ' charactors required.'; //length
    } else if (input.dataset.match) {
        var other = document.getElementById(input.dataset.match);
        if (other && input.value !== other.value) {
            ok = false;
            msg = 'Paswords do not match.'; //check pass
        }
    }

    input.classList.toggle('mistake', !ok);
    if (errEl) { //if error happen, show msg
        errEl.textContent = msg;
        errEl.classList.toggle('show', !ok);
    }
    return ok;
}

// Check all required fields 
function validateForm(form) {
    var allGood = true;
    var fields = form.querySelectorAll('input[required], select[required], textarea[required]');
    for (var i = 0; i < fields.length; i++) {
        if (!validateField(fields[i])) { //if field is incorrect , form is not valied
            allGood = false; //assignn false
        }
    }
    return allGood;
}

// validate each field as the user types
document.addEventListener('DOMContentLoaded', function() {
    var allInputs = document.querySelectorAll('input, select, textarea');
    for (var i = 0; i < allInputs.length; i++) {
        allInputs[i].addEventListener('blur', function() {
            validateField(this);
        });
        allInputs[i].addEventListener('input', function() {
            if (this.classList.contains('mistake')) validateField(this);
        });
    }
});


//photo upload 
function setupUpload(zoneId, inputId, previewId) {
    var zone = document.getElementById(zoneId);
    var input = document.getElementById(inputId);
    var preview = document.getElementById(previewId);
    if (!zone || !input) return;

    zone.addEventListener('click', function() { input.click(); }); //click on zone

    // when user pick a file
    input.addEventListener('change', function() {
        if (input.files[0]) showPreview(input.files[0]);
    });

    //drag and drop 
    zone.addEventListener('dragover', function(e) {
        e.preventDefault();
        zone.style.borderColor = '#3b82f6';
    });
    zone.addEventListener('dragleave', function() {
        zone.style.borderColor = ''; //remove color when user drag
    });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        zone.style.borderColor = '';
        if (e.dataTransfer.files[0]) showPreview(e.dataTransfer.files[0]); //show preview when user drop
    });

    function showPreview(file) {
        if (!file.type.startsWith('image/')) { //if not image, show error
            toast('Please upload an image file.', 'err');
            return;
        }
        var reader = new FileReader();
        reader.onload = function(e) {
            if (preview) {
                preview.style.display = 'block';
                var img = preview.querySelector('img'); //find img element
                if (img) img.src = e.target.result; // set src
            }
            zone.style.display = 'none';
        };
        reader.readAsDataURL(file); //read file URL
    }

    //X to remove the photo
    var removeBtn = preview ? preview.querySelector('.remove-photo') : null; //find remove button
    if (removeBtn) {
        removeBtn.addEventListener('click', function() {
            input.value = ''; //when user click remove input and hide preview
            if (preview) preview.style.display = 'none';
            zone.style.display = '';
        });
    }
}


//Lost and found tabs
function setupTabs(containerSelector) {
    var buttons = document.querySelectorAll(containerSelector + ' .tab-btn');
    for (var i = 0; i < buttons.length; i++) {
        buttons[i].addEventListener('click', function() {
            var allBtns = document.querySelectorAll(containerSelector + ' .tab-btn');
            for (var j = 0; j < allBtns.length; j++) {
                allBtns[j].classList.remove('active'); //remove active class from all btns
            }
            this.classList.add('active'); //add active class to the clicked btn

            // show the right panel
            var target = this.dataset.tab;
            var panels = document.querySelectorAll('[data-panel]'); //find all panels
            for (var k = 0; k < panels.length; k++) {
                if (panels[k].dataset.panel === target) {
                    panels[k].classList.add('visible');
                } else {
                    panels[k].classList.remove('visible');
                }
            }
        });
    }
}


// search && filter
function setupSearch() {
    var searchBox = document.getElementById('searchInput');
    var catBox = document.getElementById('categoryFilter');
    var searchBtn = document.getElementById('searchBtn'); //assign
    if (!searchBtn) return;

    function doSearch() {
        var query = searchBox ? searchBox.value.toLowerCase() : ''; //lowercased
        var category = catBox ? catBox.value : 'all';

        var cards = document.querySelectorAll('.item-card');
        for (var i = 0; i < cards.length; i++) { //loop cards
            var card = cards[i];
            var text = card.textContent.toLowerCase();
            var cardCat = card.dataset.category || '';

            var matchText = !query || text.indexOf(query) >= 0; //check if text match
            var matchCat = category === 'all' || cardCat === category;

            card.style.display = (matchText && matchCat) ? '' : 'none'; //show or hide card
        }
    }

    searchBtn.addEventListener('click', doSearch);
    if (searchBox) {
        searchBox.addEventListener('keydown', function(e) {
            if (e.key === 'Enter') doSearch(); //search when user press enter
        });
    }
}


//toggle password 
function togglePass(inputId) {
    var inp = document.getElementById(inputId);
    if (!inp) return;
    inp.type = inp.type === 'password' ? 'text' : 'password';
}


//password strength bar
function setupPasswordStrength(inputId, barId) {
    var inp = document.getElementById(inputId);
    var bar = document.getElementById(barId);
    if (!inp || !bar) return;

    inp.addEventListener('input', function() {
        var val = inp.value;
        var score = 0;
        if (val.length >= 8) score++;// length check
        if (/[A-Z]/.test(val)) score++;//uppercase
        if (/[0-9]/.test(val)) score++; // number
        if (/[^A-Za-z0-9]/.test(val)) score++;  // special char

        var colors = ['#ef4444', '#f97316', '#eab308', '#22c55e'];
        bar.style.width = (score * 25) + '%';
        bar.style.background = colors[score - 1] || '#e2e8f0';
    });
}


//run when page load
document.addEventListener('DOMContentLoaded', function() {
    setupTabs('.tab-row');
    setupSearch();
    setupUpload('upload-zone', 'photo-input', 'img-preview');
    setupPasswordStrength('new-password', 'strength-fill');
});