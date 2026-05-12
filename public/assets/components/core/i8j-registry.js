/**
 * I8JRegistry - 组件注册表
 * V3.0 Phase 2 UI组件库
 *
 * 全局单例，管理所有I8JComponent实例：
 * - 注册/注销组件
 * - 按类型查找组件
 * - 批量销毁组件
 * - 全局事件广播
 */
class I8JRegistry {
    constructor() {
        this.components = new Map();
        this.typeIndex = new Map(); // type -> Set<component>
    }

    /**
     * 注册组件
     */
    register(component) {
        if (!component || this.components.has(component)) {
            return;
        }
        this.components.set(component, {
            type: component.constructor.name,
            mountedAt: Date.now(),
        });

        // 建立类型索引
        const typeName = component.constructor.name;
        if (!this.typeIndex.has(typeName)) {
            this.typeIndex.set(typeName, new Set());
        }
        this.typeIndex.get(typeName).add(component);
    }

    /**
     * 注销组件
     */
    unregister(component) {
        if (!component) return;

        const typeName = component.constructor?.name;
        if (typeName && this.typeIndex.has(typeName)) {
            this.typeIndex.get(typeName).delete(component);
        }

        this.components.delete(component);
    }

    /**
     * 获取所有已注册组件数量
     */
    get size() {
        return this.components.size;
    }

    /**
     * 按类型查找组件
     */
    findByType(typeName) {
        const set = this.typeIndex.get(typeName);
        return set ? Array.from(set) : [];
    }

    /**
     * 按DOM元素查找组件
     */
    findByElement(element) {
        for (const [component] of this.components) {
            if (component.element === element) {
                return component;
            }
        }
        return null;
    }

    /**
     * 销毁所有组件
     */
    destroyAll() {
        const all = Array.from(this.components.keys());
        all.forEach(c => {
            try {
                c.destroy();
            } catch (e) {
                console.warn('I8JRegistry.destroyAll error:', e);
            }
        });
        this.components.clear();
        this.typeIndex.clear();
    }

    /**
     * 销毁指定类型的所有组件
     */
    destroyByType(typeName) {
        const components = this.findByType(typeName);
        components.forEach(c => {
            try {
                c.destroy();
            } catch (e) {
                console.warn('I8JRegistry.destroyByType error:', e);
            }
        });
    }

    /**
     * 广播事件到所有组件
     */
    broadcast(eventName, data) {
        for (const [component] of this.components) {
            if (typeof component.onBroadcast === 'function') {
                try {
                    component.onBroadcast(eventName, data);
                } catch (e) {
                    console.warn('I8JRegistry.broadcast error:', e);
                }
            }
        }
    }
}

// 全局单例
window.I8JRegistry = new I8JRegistry();
