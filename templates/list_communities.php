<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Communities</h1>
        <a href="community.php?action=create" class="btn btn-primary">Create Community</a>
    </div>
    
    <div class="row">
        <?php while ($community = $communities->fetch_assoc()): ?>
            <div class="col-md-4 mb-4">
                <div class="card h-100">
                    <?php if ($community['image_url']): ?>
                        <img src="<?php echo htmlspecialchars($community['image_url']); ?>" class="card-img-top" alt="Community Image" style="height: 200px; object-fit: cover;">
                    <?php endif; ?>
                    <div class="card-body">
                        <h5 class="card-title"><?php echo htmlspecialchars($community['name']); ?></h5>
                        <p class="card-text"><?php echo substr(htmlspecialchars($community['description']), 0, 100) . '...'; ?></p>
                        <p class="card-text"><small class="text-muted"><?php echo $community['member_count']; ?> members</small></p>
                        <a href="community.php?action=view&id=<?php echo $community['community_id']; ?>" class="btn btn-outline-primary">View Community</a>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    </div>
</div>
