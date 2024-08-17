const featuredImageFUP = document.querySelector('.featured-image-fup');
const imagePreviewer = document.querySelector('.featured-image-previewer');

const imagePreviewerDeleteBtn = imagePreviewer.querySelector('button');
imagePreviewerDeleteBtn.addEventListener('click', function(e) {
    e.preventDefault();
    let val = document.querySelector('.featured-preview-value input');
    val.value = '';

    let inputField = imagePreviewer.querySelector('.fup-text-input');
    inputField.value = '';

    let event = new Event('change', { bubbles: true });
    val.dispatchEvent(event);
});