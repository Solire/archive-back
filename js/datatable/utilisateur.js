
$(function() {

    $(".send-info-ajax").live("click", function(e) {
        var that = this
        e.preventDefault()
        myModal.confirm(
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
                                myModal.message(
                                        "Envoie des informations de connexions",
                                        "Les informations de connexions ont été envoyées avec succès.",
                                        "Fermer",
                                        5000
                                        );
                            } else {
                                myModal.message(
                                        "Envoie des informations de connexions",
                                        '<p class="text-error">Un problème est survenu lors de l\'envoi des informations de connexions.</p>',
                                        "Fermer"
                                        );
                            }

                        },
                        error: function(data) {
                                myModal.message(
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