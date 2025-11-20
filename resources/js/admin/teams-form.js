import ClassicEditor from '@ckeditor/ckeditor5-build-classic';
document.addEventListener('DOMContentLoaded', async () => {

    const editorElement = document.querySelector('#signature');
    if (editorElement) {
        ClassicEditor
            .create(editorElement)
            .catch(error => {
                console.error(error);
            });
    }
});
