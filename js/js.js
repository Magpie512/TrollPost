// === Search / Scry ==========
const searchBar = document.getElementById('searchBar');
if (searchBar) {
    searchBar.addEventListener('input', function () {
        const query = this.value.trim().toLowerCase();
        const cards = document.querySelectorAll('.PostCard');
        let visibleCount = 0;

        cards.forEach(card => {
            const content = card.querySelector('.content-text')?.textContent.toLowerCase() ?? '';
            const author  = card.querySelector('.post-author')?.textContent.toLowerCase() ?? '';
            const matches = query === '' || content.includes(query) || author.includes(query);
            card.style.display = matches ? '' : 'none';
            if (matches) visibleCount++;
        });

        const noResults = document.getElementById('noResults');
        if (noResults) {
            noResults.style.display = (query !== '' && visibleCount === 0) ? 'block' : 'none';
        }
    });
}

// == Edit post inline =========
function toggleEdit(id) {
    const container = document.getElementById('display-' + id);
    if (!container) {
        console.error("The container for post " + id + " was not found in the mists.");
        return;
    }
    const currentText = container.querySelector('.content-text').innerText;
    container.innerHTML = `
        <form action="process.php" method="POST">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" value="${id}">
            <textarea name="content" style="width:100%;" rows="3">${currentText}</textarea>
            <div style="margin-top:5px;">
                <button type="submit" class="btn-interaction">Save</button>
                <button type="button" class="btn-interaction" onclick="location.reload()">Cancel</button>
            </div>
        </form>
    `;
}

// == Image preview ==============
function showPreview(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    document.getElementById('fileName').textContent = file.name;
    document.getElementById('previewStrip').style.display = 'flex';
    const reader = new FileReader();
    reader.onload = e => {
        const t = document.getElementById('thumbBox');
        t.style.backgroundImage = `url(${e.target.result})`;
        t.style.backgroundSize = 'cover';
        t.style.backgroundPosition = 'center';
        t.textContent = '';
    };
    reader.readAsDataURL(file);
}

function clearFile() {
    document.getElementById('post_image').value = '';
    document.getElementById('previewStrip').style.display = 'none';
    const t = document.getElementById('thumbBox');
    t.style.backgroundImage = '';
    t.textContent = 'IMG';
}

// ==== SL.php sign-in / sign-up panel toggle ============
const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const container    = document.getElementById('container');

if (signUpButton && signInButton && container) {
    signUpButton.addEventListener('click', () => {
        container.classList.add("right-panel-active");
    });
    signInButton.addEventListener('click', () => {
        container.classList.remove("right-panel-active");
    });
}