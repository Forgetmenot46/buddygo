CREATE TABLE popular_activities (
    id INT PRIMARY KEY AUTO_INCREMENT,
    post_id INT,
    month INT,
    year INT,
    join_count INT DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (post_id) REFERENCES community_posts(post_id),
    UNIQUE KEY month_year_post (month, year, post_id)
); 