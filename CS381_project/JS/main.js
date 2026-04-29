

/*Add sample for first visit */
function seedData() {
    if (localStorage.getItem('seeded')) return; // already done

    var users = [
        { id: 1, name: 'Norah AlHosain', email: '4311085@rcjy.edu.sa', password:'123456', role:'student'},
        { id: 2, name: 'Mariah AlHarbi',email: '4311346@rcjy.edu.sa', password:'123456', role:'student'},
        { id: 3, name: 'Admin User',email:'admin@rcjy.edu.sa',password:'123456', role:'admin'}
    ];
// just examples, we will change it to db later
    var lostItems = [
        { id: 1, title: 'iPhone 15 BLue', category: 'electronics', location: 'Art Club', date: 'April 12, 2026', description: 'Blue iphone with white case',icon: '🔌', image: '', owner: 'Norah AlHosain', ownerId: 1, contact: '4311085@rcjy.edu.sa', reward: '200 SAR',status: 'active'},
        { id: 2, title: 'Samsung earbuds',category: 'electronics', location: 'Gym', date: 'Jan 14, 2026', description: 'Black Samsung earbuds with pink case', icon: '🔌', image: '', owner: 'Mariah AlHarbi', ownerId: 2, contact: '4311346@rcjy.edu.sa', reward: '',status: 'active'},
        { id: 3, title: 'Toyota Car Key',category: 'keys',location: 'Parking Lot', date: 'March 13, 2026', description: 'Toyota key with small carabiner.',icon: '🔑', image: '', owner: 'Norah AlHosain', ownerId: 1, contact: '4311085@rcjy.edu.sa', reward:'50 SAR', status: 'active'},
        { id: 4, title: 'Charger', category: 'electronics', location: 'Lab B1-03',date: 'Jan 15, 2026', description: 'White Apple USB-C charger',icon: '🔌', image: '', owner: 'Mariah AlHarbi', ownerId: 2, contact: '4311346@rcjy.edu.sa', reward: '',status: 'resolved' },
        { id: 5, title: 'Grey Hoodie', category: 'clothing', location: 'Building A ( Staff )',date: 'Feb 11, 2026', description: 'grey hoodie size large with a small stain on the sleeve.',icon: '👕', image: '', owner: 'Nora AlHosain', ownerId: 1, contact: '4311085@rcjy.edu.sa', reward: '',status: 'active'},
        { id: 6, title: 'ASOS vivobook laptop', category: 'electronics', location: 'Classes B0-123', date: 'April 14, 2026', description: 'White AirPods Pro in white case. Name "Norah" written inside the lid.', icon: '🔌', image: '', owner:'Nora AlHosain',  ownerId: 1, contact: '4311085@rcjy.edu.sa', reward: '',status: 'active' }
    ];

    var foundItems = [
        { id: 101, title: 'iPhone 13',category: 'electronics', location: 'Cafeteria',date: 'Jan 14, 2026', description: 'white iPhone 13 with clear case', icon: '🔌', image: '', finder: 'Mariah AlHarbi', finderId: 2, contact: '4311346@rcjy.edu.sa', heldAt: 'self',heldLabel: 'With the finder',status: 'active'},
        { id: 102, title: 'Car Keys (Hyundai )',category:'keys',location: 'student ntrance gate', date: 'Jan 13, 2026', description: 'Hyundai key with red lanyard charm', icon: '🔑', image: '', finder: 'Norah AlHosain', finderId: 1, contact: '4311085@rcjy.edu.sa', heldAt: 'security', heldLabel: 'Security Office', status: 'active'},
        { id: 103, title: 'Samsung Charger', category: 'electronics', location: 'Lab B1-032', date: 'March 12, 2026', description: 'White Samsung charger, no cable',icon: '🔌', image: '', finder: 'Mariah AlHarbi', finderId: 2, contact: '4311346@rcjy.edu.sa', heldAt: 'dept', heldLabel: 'Department Office', status: 'resolved' },
        { id: 104, title: 'Apple Watch', category: 'electronics', location: 'Library',date: 'Feb 15, 2026', description: 'Silver Apple Watch with white band', icon: '⌚', image: '', finder: 'Norah AlHosain',  finderId: 1, contact: '4311085@rcjy.edu.sa', heldAt: 'self', heldLabel: 'With the finder',  status: 'active'},
    ];

    localStorage.setItem('users', JSON.stringify(users)); 
    localStorage.setItem('lostItems', JSON.stringify(lostItems));
    localStorage.setItem('foundItems',JSON.stringify(foundItems));
    localStorage.setItem('messages', JSON.stringify([])); //empty messages array
    localStorage.setItem('seeded', '1');
}


//Read write from local storage
function getLS(key) {
    try {
        return JSON.parse(localStorage.getItem(key))|| [];
    } catch (e) {
        return [];
    }
}

function setLS(key, value) {
    localStorage.setItem(key, JSON.stringify(value));
}

function getUser() {
    try {
        return JSON.parse(localStorage.getItem('currentUser'));// returns null if not logged in
    } catch (e) {
        return null;
    }
}


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


//togglinf open class
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
    var group = input.closest(' .input-group');
    var errEl = group? group.querySelector(' .input-error'): null;
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
            allGood = false; // assignn false
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
        zone.style.borderColor = '';//remove color when user drag
    });
    zone.addEventListener('drop', function(e) {
        e.preventDefault();
        zone.style.borderColor = '';
        if (e.dataTransfer.files[0]) showPreview(e.dataTransfer.files[0]);//show preview when user drop
    });

    function showPreview(file) {
        if (!file.type.startsWith('image/')) {//if not image, show error
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
    var removeBtn = preview ? preview.querySelector(' .remove-photo') : null; //find remove button
    if (removeBtn) { 
        removeBtn.addEventListener('click', function() {
            input.value = '';//when user click remove input and hide preview
            if (preview) preview.style.display = 'none';
            zone.style.display = '';
        });
    }
}



//Lost and found tabs
function setupTabs(containerSelector) {
    var buttons = document.querySelectorAll(containerSelector + ' .tab-btn');
    for (var i = 0; i< buttons.length; i++) {
        buttons[i].addEventListener('click', function() {//
            var allBtns = document.querySelectorAll(containerSelector + ' .tab-btn');
            for (var j = 0; j< allBtns.length;j++) {
                allBtns[j].classList.remove('active'); //remove active class from all btns
            }
            this.classList.add('active'); //add active class to the clicked btn

            // show the right panel
            var target = this.dataset.tab;
            var panels =document.querySelectorAll('[data-panel]'); //find all panels
            for (var k = 0; k < panels.length; k++) {
                if (panels[k].dataset.panel ===target) {
                    panels[k].classList.add('visible');
                } else {
                    panels[k].classList.remove('visible');
                }
            }
        });
    }
}

//dashboard
function setupDashNav() {
    var links = document.querySelectorAll('.dash-menu-item[data-sec]');
    for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function() {
            // remove active from all links
            var all = document.querySelectorAll('.dash-menu-item');
            for (var j = 0; j < all.length; j++) 
                all[j].classList.remove('active'); //remove active class from all
            this.classList.add('active');//add active class to the clicked link

            //show matching section
            var sec = this.dataset.sec;
            var sections = document.querySelectorAll('.dash-section');
            for (var k = 0; k < sections.length; k++) {
                if (sections[k].id === 'sec-' + sec) {
                    sections[k].classList.add('active');
                } else {
                    sections[k].classList.remove('active');
                }
            }
        });
    }
}


//admin page
function setupAdminNav() {
    var links = document.querySelectorAll('.admin-menu-item[data-sec]');
    for (var i = 0; i < links.length; i++) {
        links[i].addEventListener('click', function() {
            var all = document.querySelectorAll('.admin-menu-item');
            for (var j = 0; j < all.length; j++) 
                all[j].classList.remove('active');
            this.classList.add('active'); //same as dash

            var sec = this.dataset.sec; 
            var sections = document.querySelectorAll('.admin-section');
            for (var k = 0; k < sections.length; k++) {
                if (sections[k].id === 'adm-' + sec) {
                    sections[k].classList.add('active');
                } else {
                    sections[k].classList.remove('active');
                }
            }

            // update the page title to admin
            var title = document.getElementById('admin-page-title');
            if (title) title.textContent = this.textContent.trim();
        });
    }
}


// search && filter
function setupSearch() {
    var searchBox = document.getElementById('searchInput');
    var catBox = document.getElementById('categoryFilter');
    var searchBtn =document.getElementById('searchBtn'); //assign
    if (!searchBtn) return;

    function doSearch() {
        var query = searchBox ? searchBox.value.toLowerCase():'';//lowercased
        var category = catBox ? catBox.value : 'all';

        var cards = document.querySelectorAll('.item-card'); 
        for (var i = 0; i< cards.length; i++) { //loop cards
            var card = cards[i];
            var text= card.textContent.toLowerCase();
            var cardCat = card.dataset.category || '';

            var matchText = !query || text.indexOf(query) >= 0; //check if text match
            var matchCat  = category === 'all' || cardCat === category;

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


// show items
function renderCards(items, containerId, type) {
    var container = document.getElementById(containerId);
    if (!container) return;

    if (!items.length) {
        var emoji = type === 'lost'? '🔍' : '📦';
        container.innerHTML =
            '<div class="nothing-yet">' +
            '<div class="big-emoji">' + emoji + '</div>' +
            '<h3>No items yet</h3>' +
            '<p>Be the first to post one.</p>' +
            '</div>'; //html 
        return;
    }

    var html = '';
    for (var i = 0; i < items.length;i++) {
        var item = items[i];
//tag class and txt based on status 7 type
        var tagClass = item.status === 'resolved'? 'tag-resolved' :(type === 'lost' ?'tag-lost' :'tag-found');
        var tagText  = item.status === 'resolved' ? 'Resolved' : (type === 'lost'? 'Lost' :'Found');
// show img, if none show icon
        var photoHtml = item.image
            ? '<img src="' + item.image + '" alt="' + item.title + '">'
            : '<span style="font-size:42px">' + item.icon + '</span>';
        html +=
            '<div class="item-card" data-category="' + item.category + '" ' +
                  'onclick="location.href=\'items_details.html?id=' + item.id + '&type=' + type +'\'">' +
                '<div class="card-image">' + photoHtml + '</div>' +
                '<div class="card-title">' + item.title + '</div>' +
                '<div class="card-details">' +
                    '📍 '+ item.location + '<br>' +
                    '📅 '+ (type === 'lost' ? 'Lost' : 'Found') + ': ' + item.date +
                '</div>' +
                '<span class="tag ' + tagClass + '">' + tagText + '</span>' +
            '</div>'; //html
    }
    container.innerHTML = html;
}


//user name and login header
function renderNav() {
    var user = getUser();
    var headerRight = document.getElementById('header-right');
    if (!headerRight) return;
    if (user) {
        headerRight.innerHTML =
            '<span class="user-name-badge">👤 ' + user.name + '</span>' +
            '<button class="logout-button" onclick="logout()">Logout</button>'; //show name and logout if logged in
    } else {
        headerRight.innerHTML = //else login button
            '<a href="login.html" class="btn btn-blue btn-small">Login</a>';
    }
}


//logout function
function logout() {
    localStorage.removeItem('currentUser');
    toast('Logged out succesfully.', 'ok'); // intentional spelling mistake
    setTimeout(function() {
        location.href = 'index.html';
    }, 600);
}


//check if user is logged in and has the right role
function requireLogin(role) {
    var user = getUser();

    if (!user) { //not loges - go to login
        location.href = 'login.html';
        return null;
    }

    if (role &&user.role !== role) {
        toast('Acess denied.', 'err'); //wong role
        setTimeout(function() {history.back(); }, 800); //go back after showing message
        return null;
    }

    return user;
}


//toggle password 
function togglePass(inputId) {
    var inp = document.getElementById(inputId);
    if (!inp) return;
    inp.type = inp.type=== 'password'? 'text' :'password';
}


//generate next ID for new items 
function nextId(arr) {
    if (!arr.length) return 1;
    var max = 0;
    for (var i = 0; i < arr.length; i++) {
        if (arr[i].id > max) max = arr[i].id;
    }
    return max + 1;
}


//run when page load
document.addEventListener('DOMContentLoaded', function() {
    seedData();
    renderNav();
    setupTabs('.tab-row');
    setupDashNav();
    setupAdminNav();
    setupSearch();
    setupUpload('upload-zone', 'photo-input', 'img-preview');
    setupPasswordStrength('new-password', 'strength-fill');
});
