-- AI-CMS 示例数据
-- 创建日期: 2026-04-18

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 插入分类数据
-- ----------------------------
INSERT INTO `i8j_aicms_categories` (`id`, `parent_id`, `name`, `slug`, `description`, `sort_order`, `seo_title`, `seo_keywords`, `seo_description`, `article_count`, `status`, `level`, `path`, `created_at`, `updated_at`) VALUES
(1, NULL, '技术分享', 'tech-sharing', '分享技术干货和开发经验', 10, '技术分享', '技术,开发,编程', '技术分享栏目，提供高质量的技术文章', 0, 1, 1, '0', NOW(), NOW()),
(2, NULL, '行业动态', 'industry-news', '行业最新资讯和趋势分析', 20, '行业动态', '行业,资讯,趋势', '行业动态栏目，追踪最新行业资讯', 0, 1, 1, '0', NOW(), NOW()),
(3, NULL, '产品更新', 'product-updates', '产品功能更新和版本说明', 30, '产品更新', '产品,更新,版本', '产品更新日志，记录产品迭代历程', 0, 1, 1, '0', NOW(), NOW()),
(4, 1, '前端开发', 'frontend', '前端技术相关文章', 11, '前端开发', '前端,JavaScript,Vue,React', '前端开发技术文章', 0, 1, 2, '0-1', NOW(), NOW()),
(5, 1, '后端开发', 'backend', '后端技术相关文章', 12, '后端开发', '后端,Python,Java,PHP', '后端开发技术文章', 0, 1, 2, '0-1', NOW(), NOW()),
(6, 1, 'AI人工智能', 'ai-tech', '人工智能技术相关文章', 13, 'AI人工智能', 'AI,机器学习,深度学习', 'AI人工智能技术文章', 0, 1, 2, '0-1', NOW(), NOW());

-- ----------------------------
-- 插入标签数据
-- ----------------------------
INSERT INTO `i8j_aicms_tags` (`id`, `name`, `slug`, `description`, `color`, `article_count`, `status`, `created_at`, `updated_at`) VALUES
(1, 'Vue.js', 'vuejs', 'Vue.js前端框架', '#42b983', 0, 1, NOW(), NOW()),
(2, 'React', 'react', 'React前端框架', '#61dafb', 0, 1, NOW(), NOW()),
(3, 'Python', 'python', 'Python编程语言', '#3776ab', 0, 1, NOW(), NOW()),
(4, 'PHP', 'php', 'PHP编程语言', '#777bb4', 0, 1, NOW(), NOW()),
(5, 'Laravel', 'laravel', 'Laravel PHP框架', '#ff2d20', 0, 1, NOW(), NOW()),
(6, 'Docker', 'docker', 'Docker容器技术', '#2496ed', 0, 1, NOW(), NOW()),
(7, 'Kubernetes', 'kubernetes', 'Kubernetes容器编排', '#326ce5', 0, 1, NOW(), NOW()),
(8, 'AI', 'ai', '人工智能', '#9b59b6', 0, 1, NOW(), NOW()),
(9, 'ChatGPT', 'chatgpt', 'ChatGPT相关', '#10a37f', 0, 1, NOW(), NOW());

-- ----------------------------
-- 插入文章数据
-- ----------------------------
INSERT INTO `i8j_aicms_articles` (`id`, `title`, `slug`, `excerpt`, `content`, `content_type`, `cover_image`, `category_id`, `author_id`, `status`, `is_top`, `is_featured`, `allow_comment`, `view_count`, `like_count`, `comment_count`, `seo_title`, `seo_keywords`, `seo_description`, `published_at`, `version`, `created_at`, `updated_at`) VALUES
(1, '欢迎使用八界AI-CMS内容管理系统', 'welcome-to-aicms', '欢迎使用八界AI-CMS，这是一款集成了人工智能技术的新一代内容管理系统，为您提供更智能、更高效的内容创作体验。', '<h2>欢迎使用八界AI-CMS</h2><p>八界AI-CMS是一款专为现代内容创作者设计的内容管理系统，融合了先进的人工智能技术，帮助您更高效地管理内容、创作文章。</p><h3>核心特性</h3><ul><li><strong>AI智能助手</strong> - 内置AI写作助手，帮助您快速生成高质量内容</li><li><strong>可视化编辑</strong> - 直观的可视化编辑器，让创作更简单</li><li><strong>灵活的权限管理</strong> - 完善的RBAC权限系统</li><li><strong>强大的SEO优化</strong> - 内置SEO优化功能</li></ul><h3>快速开始</h3><p>登录后台后，您可以：</p><ol><li>在「文章管理」中创建新文章</li><li>使用「AI助手」生成内容摘要和标签</li><li>在「分类管理」中组织内容结构</li><li>在「标签管理」中管理文章标签</li></ol><p>祝您使用愉快！</p>', 'html', '/uploads/covers/welcome.jpg', 1, 1, 2, 1, 1, 1, 128, 32, 5, '欢迎使用八界AI-CMS', 'AI-CMS,内容管理系统', '八界AI-CMS内容管理系统介绍和快速上手指南', NOW(), 1, NOW(), NOW()),

(2, 'Vue 3 Composition API 完全指南', 'vue3-composition-api-guide', '深入理解Vue 3 Composition API的用法和最佳实践，掌握新一代Vue开发模式。', '<h2>Vue 3 Composition API 完全指南</h2><p>Composition API是Vue 3引入的全新API设计模式，它提供了更好的逻辑复用能力和更灵活的代码组织方式。</p><h3>为什么需要Composition API？</h3><p>相比Options API，Composition API有以下优势：</p><ul><li>更好的逻辑复用</li><li>更灵活的代码组织</li><li>更好的类型推断支持</li><li>更小的生产包体积</li></ul><h3>核心概念</h3><h4>1. setup() 函数</h4><p>setup()是Composition API的入口点，在组件实例创建之前执行。</p><pre><code>import { ref, computed, onMounted } from ''vue''\n\nexport default {\n  setup() {\n    const count = ref(0)\n    const doubleCount = computed(() => count.value * 2)\n    \n    onMounted(() => {\n      console.log(''Component mounted'')\n    })\n    \n    return { count, doubleCount }\n  }\n}</code></pre><h4>2. reactive() 和 ref()</h4><p>reactive()用于创建响应式对象，ref()用于创建响应式基本类型。</p><h4>3. 生命周期钩子</h4><p>Composition API中的生命周期钩子需要从vue显式导入：</p><pre><code>import { onMounted, onUpdated, onUnmounted } from ''vue''</code></pre><h3>总结</h3><p>Composition API为Vue开发者带来了更现代、更灵活的编程方式，是Vue 3的核心特性之一。</p>', 'html', '/uploads/covers/vue3.jpg', 4, 1, 2, 0, 1, 0, 256, 48, 8, 'Vue 3 Composition API 完全指南', 'Vue 3,Composition API,前端开发', '深入理解Vue 3 Composition API的用法和最佳实践', NOW(), 1, NOW(), NOW()),

(3, 'Docker容器化部署实战：从入门到精通', 'docker-deployment-guide', '全面讲解Docker容器化部署的实战技巧，包括镜像构建、网络配置、数据持久化等核心内容。', '<h2>Docker容器化部署实战</h2><p>Docker已经成为现代应用部署的标准解决方案，本文将带您从零开始掌握Docker容器化部署。</p><h3>Docker核心概念</h3><ul><li><strong>镜像(Image)</strong> - 容器的基础模板</li><li><strong>容器(Container)</strong> - 镜像的运行实例</li><li><strong>仓库(Registry)</strong> - 存储和分发镜像</li></ul><h3>Dockerfile编写技巧</h3><pre><code>FROM php:8.2-fpm-alpine\n\n# 安装系统依赖\nRUN apk add --no-cache git curl libpng-dev\n\n# 安装PHP扩展\nRUN docker-php-ext-install pdo_mysql gd\n\n# 复制应用代码\nCOPY . /var/www/html\n\n# 设置工作目录\nWORKDIR /var/www/html\n\n# 启动命令\nCMD ["php-fpm"]</code></pre><h3>docker-compose编排</h3><p>使用docker-compose可以轻松管理多容器应用：</p><pre><code>version: ''3.8''\nservices:\n  web:\n    build: .\n    ports:\n      - "80:80"\n  db:\n    image: mysql:8.0\n    environment:\n      MYSQL_ROOT_PASSWORD: secret</code></pre><h3>最佳实践</h3><ol><li>使用多阶段构建减小镜像体积</li><li>合理利用.dockerignore文件</li><li>使用轻量级基础镜像</li><li>分离应用层和数据层</li></ol><h3>总结</h3><p>Docker容器化部署可以极大提升开发效率和部署可靠性，是现代DevOps的必备技能。</p>', 'html', '/uploads/covers/docker.jpg', 5, 1, 2, 0, 0, 1, 189, 27, 3, 'Docker容器化部署实战', 'Docker,容器化,DevOps,部署', 'Docker容器化部署实战指南，从入门到精通', NOW(), 1, NOW(), NOW());

-- ----------------------------
-- 插入文章标签关联数据
-- ----------------------------
INSERT INTO `i8j_aicms_article_tags` (`article_id`, `tag_id`, `created_at`) VALUES
(1, 8, NOW()),
(2, 1, NOW()),
(3, 6, NOW());

-- ----------------------------
-- 更新分类文章数量
-- ----------------------------
UPDATE `i8j_aicms_categories` SET `article_count` = 1 WHERE `id` = 1;
UPDATE `i8j_aicms_categories` SET `article_count` = 1 WHERE `id` = 4;
UPDATE `i8j_aicms_categories` SET `article_count` = 1 WHERE `id` = 5;

-- ----------------------------
-- 更新标签文章数量
-- ----------------------------
UPDATE `i8j_aicms_tags` SET `article_count` = 1 WHERE `id` = 8;
UPDATE `i8j_aicms_tags` SET `article_count` = 1 WHERE `id` = 1;
UPDATE `i8j_aicms_tags` SET `article_count` = 1 WHERE `id` = 6;

SET FOREIGN_KEY_CHECKS = 1;
