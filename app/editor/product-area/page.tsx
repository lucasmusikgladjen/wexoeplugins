import ProductAreaBuilder from '@/components/product-area/ProductAreaBuilder';
import { emptyProductAreaState } from '@/lib/product-area-types';

export const dynamic = 'force-dynamic';

export default function CreateProductAreaPage() {
  return <ProductAreaBuilder initialState={emptyProductAreaState()} mode="create" />;
}
