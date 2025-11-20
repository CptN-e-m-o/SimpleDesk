import ClassicEditor from '@ckeditor/ckeditor5-build-classic';

document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('textarea.rich-editor').forEach((element) => {
        ClassicEditor.create(element).catch((error) => console.error(error));
    });
});
