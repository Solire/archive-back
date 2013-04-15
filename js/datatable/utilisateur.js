
$(function() {

    var myModalUtilisateur = {
        confirm: function(heading, question, cancelButtonTxt, okButtonTxt, callback) {
            var confirmModal =
                    $('<div class="modal hide fade">' +
                    '<div class="modal-header">' +
                    '<a class="close" data-dismiss="modal" >&times;</a>' +
                    '<h3>' + heading + '</h3>' +
                    '</div>' +
                    '<div class="modal-body">' +
                    '<p>' + question + '</p>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                    '<a href="#" class="btn" data-dismiss="modal">' +
                    cancelButtonTxt +
                    '</a>' +
                    '<a href="#" id="okButton" class="btn btn-danger">' +
                    okButtonTxt +
                    '</a>' +
                    '</div>' +
                    '</div>');

            confirmModal.find('#okButton').click(function(event) {
                event.preventDefault()
                callback();
                confirmModal.modal('hide');
            });

            confirmModal.modal('show');
        },
        message: function(heading, message, closeButtonTxt, closeDelay) {

            var messageModal =
                    $('<div class="modal hide fade">' +
                    '<div class="modal-header">' +
                    '<a class="close" data-dismiss="modal" >&times;</a>' +
                    '<h3>' + heading + '</h3>' +
                    '</div>' +
                    '<div class="modal-body">' +
                    '<p>' + message + '</p>' +
                    '</div>' +
                    '<div class="modal-footer">' +
                    '<a href="#" id="closeButton" data-dismiss="modal" class="btn btn-primary">' +
                    closeButtonTxt +
                    '</a>' +
                    '</div>' +
                    '</div>');

            messageModal.modal('show');
            if (closeDelay) {
                messageModal.delay(closeDelay).queue(function(nxt) {
                    messageModal.modal("hide")
                    nxt(); // continue the queue
                })
            }

        }
    }



    $(".send-info-ajax").live("click", function(e) {
        var that = this
        e.preventDefault()
        myModalUtilisateur.confirm(
                "Envoie des informations de connexions",
                "Êtes-vous sûr de vouloir envoyer les informations de connexions ?",
                "Annuler",
                "Envoyer",
                function() {
                    $.ajax({
                        type: "POST",
                        url: $(that).attr("href"),
                        success: function(data) {
                            if (data.status) {
                                myModalUtilisateur.message(
                                        "Envoie des informations de connexions",
                                        "Les informations de connexions ont été envoyées avec succès.",
                                        "Fermer",
                                        5000
                                        );
                            } else {
                                myModalUtilisateur.message(
                                        "Envoie des informations de connexions",
                                        '<p class="text-error">Un problème est survenu lors de l\'envoi des informations de connexions.</p>',
                                        "Fermer"
                                        );
                            }

                        },
                        error: function(data) {
                                myModalUtilisateur.message(
                                        "Envoie des informations de connexions",
                                        '<p class="text-error">Un problème est survenu lors de l\'envoi des informations de connexions.</p>',
                                        "Fermer"
                                        );
                        },
                        dataType: 'json'
                    });
                }
        )




    })

})