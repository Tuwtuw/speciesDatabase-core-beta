<?php
session_start();
require_once '../../config.php';
require_once '../includes/auth_validate.php';

// Titles
$page_title = 'Users Accounts';
$title = $page_title.' - '.$site_name;

//Only super admin is allowed to access this page
if ($_SESSION['user_type'] !== 'super')
{
    // show permission denied message
    header('HTTP/1.1 401 Unauthorized', true, 401);
    
    exit("401 Unauthorized");
}

// Get Input data from query string
$search_string = filter_input(INPUT_GET, 'search_string');
$order_by = filter_input(INPUT_GET, 'order_by');
$order_dir = filter_input(INPUT_GET, 'order_dir');
$del_id = filter_input(INPUT_GET, 'del_id');

// Get current page.
$page = filter_input(INPUT_GET, 'page');

// Per page limit for pagination.
$pagelimit = 20;
if (!$page)
{
    $page = 1;
}

// If filter types are not selected we show latest created data first
if (!$order_by)
{
    $order_by = 'id';
}
if (!$order_dir)
{
    $order_dir = 'desc';
}

// Get DB instance. i.e instance of MYSQLiDB Library
$db = getDbInstance();
$cols = array('id', 'name', 'email', 'username', 'user_type');
$db->orderBy($order_by, $order_dir);

// Start building query according to input parameters.
// If search string
if ($search_string)
{
    $db->where('username', '%'.$search_string.'%', 'like');
}

// If order by option selected
if ($order_dir)
{
    $db->orderBy($order_by, $order_dir);
}

// Set pagination limit
$db->pageLimit = $pagelimit;

// Get result of the query.
$result = $db->arraybuilder()->paginate('users_accounts', $page, $cols);
$total_pages = $db->totalPages;

// Get columns for order filter
foreach ($result as $value)
{
    foreach ($value as $col_name => $col_value)
    {
        $filter_options[$col_name] = $col_name;
    }
    // Execute only once
    break;
}
?>
<!doctype html>
<html lang="pt">
<?php include_once BASE_PATH.'/modules/header.php'; ?>

<body class="bg-light <?php echo lcfirst($page_title); ?>">
<?php include_once BASE_PATH.'/admin/modules/menu.php'; ?>
<div class="container-fluid" role="main">
    <!-- Toolbar -->
    <div class="toolbar sticky-top row my-2 p-2">
        <div class="col-12">
            <h4 class="float-left"><?php echo $page_title; ?></h4>
            <div class="float-right">
                <a href="user_account_add.php?task=add&id=0" class="btn btn-primary btn-sm" role="button"><i class="fas fa-plus"></i>New</a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="my-3 p-3 bg-white rounded box-shadow">
                <?php include('../includes/flash_messages.php'); ?>
                <?php
                if (isset($del_stat) && $del_stat == 1)
                {
                    echo '<div class="alert alert-info">Successfully deleted</div>';
                }
                ?>
                <!-- Filters -->
                <form class="form-inline mb-3" action="">
                    <label class="sr-only">Search</label>
                    <input type="text" class="form-control mr-2" name="search_string" value="<?php echo $search_string; ?>" placeholder="Search">
                    <label for="input_order" class="mr-2">Order By</label>
                    <select name="order_by" class="form-control mr-2">
                        <?php
                        foreach ($filter_options as $option):
                            ($order_by === $option) ? $selected = 'selected' : $selected = '';
                            echo ' <option value="'.$option.'" '.$selected.'>'.$option.'</option>';
                        endforeach;
                        ?>
                    </select>
                    <select name="order_dir" class="form-control mr-2" id="input_order">
                        <option value="Asc" <?php
                        if ($order_dir == 'Asc')
                        {
                            echo 'selected';
                        }
                        ?>>Asc</option>
                        <option value="Desc" <?php
                        if ($order_dir == 'Desc')
                        {
                            echo 'selected';
                        }
                        ?>>Desc</option>
                    </select>
                    <input type="submit" value="Go" class="btn btn-primary">
                </form>

                <!-- Table -->
                <table class="table table-striped table-hover table-sm">
                    <caption><?php echo $page_title; ?></caption>
                    <thead>
                        <tr width="100%">
                            <th width="5%">ID</th>
                            <th width="25%">Name</th>
                            <th width="25%">Email</th>
                            <th width="20%">User name</th>
                            <th width="20%">User type</th>
                            <th width="5%" colspan="2">State</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($result as $row): ?>
                        <tr>
                            <td><?php echo $row['id']; ?></td>
                            <td><?php echo htmlspecialchars($row['name']); ?></td>
                            <td><?php echo htmlspecialchars($row['email']); ?></td>
                            <td><a href="user_account_edit.php?task=edit&id=<?php echo $row['id']?>"><?php echo htmlspecialchars($row['username']); ?></a></td>
                            <td><?php echo htmlspecialchars($row['user_type']); ?></td>
                            <td><a data-toggle="modal" data-target="#delete-<?php echo $row['id']; ?>"><i class="fas fa-trash"></i></a></td>
                        </tr>
                        <!-- Delete Confirmation Modal-->
                        <div class="modal fade" id="delete-<?php echo $row['id'] ?>" role="dialog">
                            <div class="modal-dialog">
                                <form action="delete_user.php" method="POST">
                                    <!-- Modal content-->
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <button type="button" class="close" data-dismiss="modal">&times;</button>
                                            <h4 class="modal-title">Confirm</h4>
                                        </div>
                                        <div class="modal-body">
                                            <input type="hidden" name="del_id" id = "del_id" value="<?php echo $row['id'] ?>">
                                            <p>Are you sure you want to delete this User?</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="submit" class="btn btn-default pull-left">Yes</button>
                                            <button type="button" class="btn btn-default" data-dismiss="modal">No</button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php if ($total_pages > 1): ?>
                <!-- Pagination -->
                <nav aria-label="<?php echo $page_title?> navigation">
                    <?php
                    if (!empty($_GET))
                    {
                        //we must unset $_GET[page] if previously built by http_build_query function
                        unset($_GET['page']);
                        //to keep the query sting parameters intact while navigating to next/prev page,
                        $http_query = "?".http_build_query($_GET);
                    }
                    else
                    {
                        $http_query = "?";
                    }
                    ?>

                    <ul class="pagination">
                        <!--li class="page-item">
                            <a href="accounts.php<?php echo $http_query; ?>&page=" class="page-link" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li-->
                        <?php
                        for ($i = 1; $i <= $total_pages; $i++)
                        {
                            ($page == $i) ? $li_class = ' active' : $li_class = "";
                            echo '<li class="page-item'.$li_class.'"><a href="accounts.php'.$http_query.'&page='.$i.'" class="page-link">'.$i.'</a></li>';
                        }
                        ?>
                        <!--li class="page-item">
                            <a href="accounts.php<?php echo $http_query; ?>&page=" class="page-link" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li-->
                    </ul>
                </nav>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include_once BASE_PATH.'/modules/footer.php'; ?>
</body>
</html>
