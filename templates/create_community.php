<div class="container mt-4">
    <h1>Create New Community</h1>
    
    <form action="community.php?action=create" method="POST" enctype="multipart/form-data" class="mt-4">
        <div class="mb-3">
            <label for="name" class="form-label">Community Name</label>
            <input type="text" class="form-control" id="name" name="name" required>
        </div>
        
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea class="form-control" id="description" name="description" rows="4" required></textarea>
        </div>
        
        <div class="mb-3">
            <label for="community_image" class="form-label">Community Image</label>
            <input type="file" class="form-control" id="community_image" name="community_image" accept="image/*">
        </div>
        
        <button type="submit" class="btn btn-primary">Create Community</button>
        <a href="community.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
