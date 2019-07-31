function TigrovPhotoWidget(wrapperId, fieldId, imageId, cancelId, detailIds, options) {
    var wrapper = $('#' + wrapperId);
    var field = $('#' + fieldId);
    var img = $('#' + imageId);
    var cancel = $('#' + cancelId);
    var src = img.attr('src');
    var cropper = null;

    options['crop'] = function (e) {
        $.each(detailIds, function(key, id) {
            $('#' + id).val(e.detail[key]);
        });
    };

    wrapper.on('dragover', false).on('drop', function(e){
        e.preventDefault();
        e.stopPropagation();
        readFile(e.originalEvent.dataTransfer.files[0]);
    });
    field.parent().on('dragover', false).on('drop', function(e){
        e.preventDefault();
        e.stopPropagation();
        readFile(e.originalEvent.dataTransfer.files[0]);
    });

    field.change(function(e){
        readFile(this.files[0]);
    });

    img.click(function(e){
        e.preventDefault();
        e.stopPropagation();
        field.click();
    });

    cancel.click(function(e){
        e.preventDefault();
        e.stopPropagation();
        field.val('');
        img.attr('src', src);
        cropper.uncreate();
        cropper = null;
        $.each(detailIds, function(key, id) {
            $('#' + id).val('');
        });
        cancel.hide();
    });

    function readFile(file) {
        var reader = new FileReader();
        reader.onload = function (e) {
            if (!cropper) {
                cropper = img.cropper(options).data('cropper');
                cropper.replace(e.target.result);
                cancel.show();
            } else {
                cropper.replace(e.target.result);
                cropper.reset();
            }
        };
        reader.readAsDataURL(file);
    }
}