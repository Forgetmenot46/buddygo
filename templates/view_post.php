<div class="container mt-4">
    <div class="card">
        <div class="card-body">
            <h1 class="card-title"><?php echo htmlspecialchars($post['title']); ?></h1>
            <h5 class="card-subtitle mb-3 text-muted">
                <i class="fas fa-map-marker-alt"></i> <?php echo htmlspecialchars($post['destination']); ?>
            </h5>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <p><strong>ผู้สร้างโพสต์:</strong> <?php echo htmlspecialchars($post['creator_name']); ?></p>
                    <p><strong>วันที่เดินทาง:</strong> <?php echo date('d/m/Y', strtotime($post['travel_date'])); ?></p>
                </div>
                <div class="col-md-6">
                    <p><strong>จำนวนคน:</strong> <?php echo $post['current_members']; ?>/<?php echo $post['max_members']; ?> คน</p>
                    <p><strong>สถานะ:</strong> <?php echo $post['status'] === 'active' ? 'เปิดรับสมัคร' : 'ปิดรับสมัคร'; ?></p>
                </div>
            </div>
            
            <div class="card mb-4">
                <div class="card-body">
                    <h5 class="card-title">รายละเอียด</h5>
                    <p class="card-text"><?php echo nl2br(htmlspecialchars($post['description'])); ?></p>
                </div>
            </div>
            
            <?php if ($post['status'] === 'active' && $post['current_members'] < $post['max_members']): ?>
                <a href="community.php?action=join&id=<?php echo $post['post_id']; ?>" class="btn btn-primary mb-4">เข้าร่วม</a>
            <?php endif; ?>
            
            <!-- Comments Section -->
            <h5 class="mb-3">ความคิดเห็น</h5>
            <form action="community.php?action=comment&id=<?php echo $post['post_id']; ?>" method="POST" class="mb-4">
                <div class="mb-3">
                    <textarea class="form-control" name="comment" rows="2" required placeholder="แสดงความคิดเห็น..."></textarea>
                </div>
                <button type="submit" class="btn btn-primary">ส่งความคิดเห็น</button>
            </form>
            
            <div class="comments-section">
                <?php while ($comment = $comments->fetch_assoc()): ?>
                    <div class="card mb-2">
                        <div class="card-body">
                            <p class="card-text"><?php echo nl2br(htmlspecialchars($comment['comment'])); ?></p>
                            <small class="text-muted">
                                โดย <?php echo htmlspecialchars($comment['username']); ?> - 
                                <?php echo date('d/m/Y H:i', strtotime($comment['created_at'])); ?>
                            </small>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        </div>
    </div>
</div>
