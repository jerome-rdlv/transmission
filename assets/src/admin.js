(function IIFE() {
    
    var aidInput = document.querySelector('input[name="jdt_aid"]');
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
            console.log('aidInput', aidInput);
            console.log('aidLabel', aidLabel);
            aidInput.value = attachment.id;
            console.log(attachment);
            var filename = attachment.attributes.url.replace(/^.*?([^\/]+)$/, '$1');
            aidLabel.innerText = filename;
        });
        
        window.open();
    });
    
})();