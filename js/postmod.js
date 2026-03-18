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
            <div style="margin-top: 5px;">
                <button type="submit" class="btn-interaction">Save</button>
                <button type="button" class="btn-interaction" onclick="location.reload()">Cancel</button>
            </div>
        </form>
    `;
}