<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>โพสต์ทั้งหมด</h1>
        <a href="community.php?action=create" class="btn btn-primary">สร้างโพสต์ใหม่</a>
    </div>
    
    <div class="row">
        <?php while ($post = $posts->fetch_assoc()): ?>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h5>
                        <h6 class="card-subtitle mb-2 text-muted">
                            <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($post['destination']); ?>
                        </h6>
                        <p class="card-text"><?php echo substr(htmlspecialchars($post['description']), 0, 150) . '...'; ?></p>
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <small class="text-muted">
                                    <i class="fas fa-users"></i> <?php echo $post['current_members']; ?>/<?php echo $post['max_members']; ?> คน
                                </small>
                                <br>
                                <small class="text-muted">
                                    <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($post['travel_date'])); ?>
                                </small>
                            </div>
                            <div>
                                <small class="text-muted">โดย <?php echo htmlspecialchars($post['creator_name']); ?></small>
                                <br>
                                <a href="community.php?action=view&id=<?php echo $post['post_id']; ?>" class="btn btn-sm btn-outline-primary">ดูรายละเอียด</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>
