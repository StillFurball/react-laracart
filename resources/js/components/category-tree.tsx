import { ChevronRight } from 'lucide-react';
import { useState } from 'react';
import { cn } from '@/lib/utils';
import type { CategoryNode } from '@/types';

type TreeProps = {
    activeSlug: string;
    onSelect: (slug: string) => void;
};

function containsSlug(category: CategoryNode, slug: string): boolean {
    return (
        category.slug === slug ||
        (category.children ?? []).some((child) => containsSlug(child, slug))
    );
}

function CategoryItem({
    category,
    activeSlug,
    onSelect,
}: TreeProps & { category: CategoryNode }) {
    const [isExpanded, setIsExpanded] = useState(() =>
        containsSlug(category, activeSlug),
    );
    const children = category.children ?? [];
    const isActive = category.slug === activeSlug;

    return (
        <li>
            <div className="flex items-center gap-1">
                <button
                    type="button"
                    aria-current={isActive ? 'true' : undefined}
                    onClick={() => {
                        setIsExpanded(true);
                        onSelect(category.slug);
                    }}
                    className={cn(
                        'flex-1 rounded-md px-3 py-1.5 text-left text-body transition-colors hover:bg-accent',
                        isActive &&
                            'bg-primary text-primary-foreground hover:bg-primary',
                    )}
                >
                    {category.name}
                </button>
                {children.length > 0 && (
                    <button
                        type="button"
                        aria-expanded={isExpanded}
                        aria-label={`${isExpanded ? 'Tutup' : 'Buka'} ${category.name}`}
                        onClick={() => setIsExpanded((current) => !current)}
                        className="rounded-md p-2 transition-colors hover:bg-accent"
                    >
                        <ChevronRight
                            className={cn(
                                'size-4 transition-transform',
                                isExpanded && 'rotate-90',
                            )}
                        />
                    </button>
                )}
            </div>

            {isExpanded && children.length > 0 && (
                <CategoryTree
                    categories={children}
                    activeSlug={activeSlug}
                    onSelect={onSelect}
                    className="pl-4 text-soft"
                />
            )}
        </li>
    );
}

export default function CategoryTree({
    categories,
    activeSlug,
    onSelect,
    className,
}: TreeProps & { categories: CategoryNode[]; className?: string }) {
    return (
        <ul className={className}>
            {categories.map((category) => (
                <CategoryItem
                    key={category.id}
                    category={category}
                    activeSlug={activeSlug}
                    onSelect={onSelect}
                />
            ))}
        </ul>
    );
}
