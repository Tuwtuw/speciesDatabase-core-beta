<?php
/*
 *  Taxa recursive tree
 */
function taxa_recursive_tree($id)
{
    // Connect to DB
    $db = getDbInstance();

    // Taxa
    $cols = array(
        'tx.id txID', 'tx.name name', 'tx.id_parent parentId', 'tx.id_type level',
        'txtp.name typeName'
    );
    $db->join('systematics_taxa_types txtp', 'tx.id_type = txtp.id', 'LEFT');
    $db->where('tx.id_parent', $id);
    $db->where('tx.published', 1);
    $db->orderBy('tx.name', 'asc');
    $taxa = $db->get('systematics_taxa tx', null, $cols);

    foreach ($taxa as $taxon)
    {
        $i = 0;
        if ($i == 0) echo '<ul>';
        echo '<li><span class="badge badge-light">'.$taxon['name'].'</span>';
        taxa_recursive_tree($taxon['txID']);

        // Species
        $cols = array('sp.id id', 'sp.image image');
        $db->where('sp.published', 1);
        $db->where('sp.validate', 1);
        $db->where('sp.id_taxon', $taxon['txID']);
        $db->orderBy('sp.genus', 'asc');
        $db->orderBy('sp.species', 'asc');
        $species = $db->get('systematics_species sp', null, $cols);

        if($db->count)
        {
            echo '<ul>'."\n";
            foreach ($species as $sp)
            {
                echo '<li>'."\n";

                // Species class
                require_once 'libraries/Systematics/Species.php';
                $species = new Species();
                ?>
                <!-- Nomenclature -->
                <span class="badge badge-light"><a href="species_details.php?id=<?php echo $sp['id']; ?>" target="_blank"><?php echo $species->getNomenclature($sp['id']); ?></a></span>
                <!-- Authoring -->
                <?php if ($species->getAuthoring($sp['id'])): ?>
                <span class="badge badge-light"><?php echo $species->getAuthoring($sp['id']); ?></span>
                <?php endif; ?>

                <?php
                // Badges
                $db->where('bdg.published', 1);
                $badges = $db->get('systematics_badges bdg', null);
                foreach ($badges as $badge):
                    include('badges/'.$badge['badge_type'].'/index.php');
                endforeach;
                ?>

                <?php
                echo '</li>'."\n";
            }
            echo '</ul>'."\n";
        }
        echo '</li>';
        $i++;
        if($i > 0) echo '</ul>';
    }
}
?>
