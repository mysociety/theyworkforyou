            <?php
            foreach ($calendar as $year => $months) {
                foreach ($months as $month => $dates) {
                    include '_calendar.php';
                }
            } ?>
