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

const signUpButton = document.getElementById('signUp');
const signInButton = document.getElementById('signIn');
const container = document.getElementById('container');

if (signUpButton && signInButton && container) {
    signUpButton.addEventListener('click', () => {
        container.classList.add("right-panel-active");
    });
    signInButton.addEventListener('click', () => {
        container.classList.remove("right-panel-active");
    });
}