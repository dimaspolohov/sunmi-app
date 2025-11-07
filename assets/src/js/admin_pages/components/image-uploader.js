const $ = jQuery;

export default function () {
    $.each($('.js-image-uploader'), function () {
        const $this = $(this);
        const $input = $this.find('input');
        const $img = $this.find('img');
        const $add = $this.find('.js-image-upload')
        const $remove = $this.find('.js-image-remove')

        const defaultImage = $this.attr('data-default-image');

        const frame = wp.media({
            multiple: false
        });

        frame.on('select', function () {
            // Get media attachment details from the frame state
            const attachment = frame.state().get('selection').first().toJSON();

            $img.attr('src', attachment.url);
            $input.val(attachment.url);
            $remove.show();
        });

        $add.on('click', function (e) {
            e.preventDefault();
            frame.open();
        })

        $remove.on('click', function (e) {
            e.preventDefault();
            const confirm = window.confirm('Вы уверены?')

            if (confirm) {
                $img.attr('src', defaultImage);
                $input.val('');
                $remove.hide();
            }
        }).toggle($input.val() !== '')
    });
}