<?php include 'includes/header.php'; ?>

<div class="content-box">
    <h2 class="text-warning mb-3">Tips for Used Car Buyers</h2>
    <p>Buying a used car can be smart and affordable — but you must inspect carefully before finalizing.</p>

    <div class="accordion mt-3" id="carTipsAccordion">
        <!-- Tip 1 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingOne">
                <button class="accordion-button" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                    1. Check Vehicle History
                </button>
            </h2>
            <div id="collapseOne" class="accordion-collapse collapse show" 
                aria-labelledby="headingOne" data-bs-parent="#carTipsAccordion">
                <div class="accordion-body">
                    Review the service records and accident history before buying. You can also use online car history tools to verify ownership and previous claims.
                </div>
            </div>
        </div>

        <!-- Tip 2 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingTwo">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                    2. Test Drive Thoroughly
                </button>
            </h2>
            <div id="collapseTwo" class="accordion-collapse collapse" 
                aria-labelledby="headingTwo" data-bs-parent="#carTipsAccordion">
                <div class="accordion-body">
                    Always take a long test drive to check comfort, noise, engine smoothness, and brake performance.
                </div>
            </div>
        </div>

        <!-- Tip 3 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingThree">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                    3. Verify Documents
                </button>
            </h2>
            <div id="collapseThree" class="accordion-collapse collapse" 
                aria-labelledby="headingThree" data-bs-parent="#carTipsAccordion">
                <div class="accordion-body">
                    Check RC, insurance, and pollution certificate validity. Ensure the engine and chassis numbers match the RC book.
                </div>
            </div>
        </div>

        <!-- Tip 4 -->
        <div class="accordion-item">
            <h2 class="accordion-header" id="headingFour">
                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" 
                    data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                    4. Compare Prices
                </button>
            </h2>
            <div id="collapseFour" class="accordion-collapse collapse" 
                aria-labelledby="headingFour" data-bs-parent="#carTipsAccordion">
                <div class="accordion-body">
                    Check similar models online to ensure you’re paying a fair price based on age, mileage, and condition.
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
