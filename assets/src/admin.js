(function IIFE() {
    
    var aidInput = document.querySelector('[name="jdt_aid"]');
    var aidLabel = document.querySelector('.JdtForm-selection');
    
    document.getElementById('jdt-audio-file').addEventListener('click', function () {
        var window = wp.media({
            title: 'Choisir un fichier',
            library: {
                type: 'audio'
            },
            multiple: false,
            button: {
                text: 'Choisir'
            }
        });
        
        window.on('select', function () {
            var attachment = window.state().get('selection').first();
            aidInput.value = attachment.id;
            aidLabel.innerText = attachment.attributes.url.replace(/^.*?([^\/]+)$/, '$1');
        });
        
        window.open();
    });
    
    var aidFileRow = document.querySelector('.JdtForm-row.file');
    var aidUrlRow = document.querySelector('.JdtForm-row.url');
    document.querySelector('[name="jdt_type_url"]').addEventListener('change', function (e) {
        if (e.target.checked) {
            aidFileRow.setAttribute('hidden', '');
            aidUrlRow.removeAttribute('hidden');
        }
        else {
            aidFileRow.removeAttribute('hidden');
            aidUrlRow.setAttribute('hidden', '');
        }
    });
    
})();