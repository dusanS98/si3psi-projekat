<!--Autor: Dušan Stanivuković 2017/0605-->


<div class="container">
    <?php
    if (session()->getFlashdata("messages")) {
        echo "<div class='alert alert-info alert-dismissible text-center mx-auto my-4'>";
        echo "<strong>" . session()->getFlashdata("messages") . "</strong>";
        echo "<button type='button' class='close' data-dismiss='alert' aria-label='Close'>";
        echo "<span aria-hidden='true'>&times;</span>";
        echo "</button>";
        echo "</div>";
    }
    ?>
    <div class="row">
        <div class="col-md-10 mx-auto my-4 bg-light rounded">
            <form enctype="multipart/form-data" accept-charset="UTF-8" method="post"
                  action="<?php echo site_url("Room/saveChanges"); ?>">
                <div class="form-row mt-4">
                    <div class=" col-md-4 mb-3 ml-auto">
                        <label for="validationDefaultType">Tip:</label>
                    </div>
                    <div class="col-md-4 mb-3 mr-auto">
                        <input type="text" value="<?php echo $room['type']; ?>" class="form-control" name="type"
                               id="validationDefaultType" required/>
                    </div>
                </div>
                <div class="form-row mt-4">
                    <div class=" col-md-4 mb-3 ml-auto">
                        <label for="validationDefaultDescription">Opis:</label>
                    </div>
                    <div class="col-md-4 mb-3 mr-auto">
                        <textarea class="form-control" name="description" id="validationDefaultDescription"
                                  required><?php echo $room["description"]; ?>
                        </textarea>
                    </div>
                </div>
                <div class="form-row col-md-4 mx-auto mt-3 mb-4">
                    <input type="hidden" name="roomId" value="<?php echo $room['roomId']; ?>">
                    <button class="btn btn-primary" type="submit">
                        Sačuvaj
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
